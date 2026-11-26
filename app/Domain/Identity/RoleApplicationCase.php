<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use App\Domain\Operations\CommandRejection;

/**
 * A person's application to switch from Investor to one other role (Erastus, 23 Nov 2026): Business
 * representative, with the company's KYB (BRS v3 KYC-R03: RDB registration, TIN, Articles of
 * Association, shareholder register, directors and beneficial owners with their IDs), or Auditor, with
 * the ICPAR licence and the auditor's coverage and conflicts (AUD-R01). It holds answers and document
 * ids only; it never verifies anyone and grants nothing. Compliance approves, asks for more or rejects,
 * and only approval switches the role.
 *
 * An Investor may instead apply to invest for an institution (26 Nov 2026, BRS v3 KYC-R02): the
 * institution's KYB exactly as for a business, plus its type, its monthly distribution commitment, a
 * bank account in its own name and a letter authorising this person to act for it. They stay an
 * Investor; approval records the institution they invest for.
 *
 * @phpstan-type Person array{full_name: string, id_type: 'national_id'|'passport', id_number: string, nationality: string}
 * @phpstan-type Owner array{full_name: string, id_type: 'national_id'|'passport', id_number: string, nationality: string, ownership_pct: string, pep: bool}
 *
 * @phpstan-import-type Address from RwandaAddress
 *
 * @phpstan-type Company array{legal_name: string, rdb_code: string, tin: string, company_type: string, incorporation_date: string, industry: string,
 *     province: string, district: string, sector: string, cell: string, street: string, phone: string}
 * @phpstan-type Representative array{title: string, phone: string, authorised: bool}
 * @phpstan-type Licence array{membership_number: string, licence_number: string, expires_on: string, firm: string}
 * @phpstan-type Coverage array{districts: list<string>, no_conflicts: bool, will_declare: bool, ethics: bool}
 * @phpstan-type Institution array{type: string, monthly_commitment: string, bank_name: string, account_name: string, account_number: string, automates: bool, own_rules: bool}
 * @phpstan-type Answers array{
 *     company?: Company, representative?: Representative, directors?: list<Person>, owners?: list<Owner>, no_major_owner?: bool,
 *     licence?: Licence, address?: Address, coverage?: Coverage, institution?: Institution, declared: bool
 * }
 * @phpstan-type RevisionItem array{key: string, label: string, note: string}
 * @phpstan-type Revision array{note: string|null, items: list<RevisionItem>, requested_at: string, before?: array{answers: Answers, documents: array<string, string>}}
 * @phpstan-type State array{role: 'business'|'auditor'|'institution', step: string, answers: Answers, documents: array<string, string>, note: string|null, revision?: Revision}
 */
final class RoleApplicationCase
{
    public const ROLES = ['business', 'auditor', 'institution'];

    /** Each role's steps in the order they are filled in; `review` is the summary they are sent from. */
    public const STEPS = [
        'business' => ['company', 'people', 'documents', 'review'],
        'auditor' => ['licence', 'coverage', 'review'],
        'institution' => ['company', 'people', 'institution', 'documents', 'review'],
    ];

    /** The company documents KYC-R03 requires. The board resolution is optional (a non-director representative's authority). */
    public const BUSINESS_DOCUMENTS = ['rdb_certificate', 'tin_certificate', 'articles', 'shareholder_register'];

    public const OPTIONAL_DOCUMENTS = ['board_resolution'];

    public const AUDITOR_DOCUMENTS = ['practising_certificate'];

    /**
     * An institution's KYB documents: the business ones, the letter or board resolution authorising this
     * person to act for it, and the bank's letter confirming the account is in the institution's name.
     */
    public const INSTITUTION_DOCUMENTS = [...self::BUSINESS_DOCUMENTS, 'authorisation_letter', 'bank_letter'];

    public const COMPANY_TYPES = ['private_limited', 'public_limited', 'cooperative', 'partnership', 'other'];

    /** The statuses in which the applicant may still change their answers. */
    public const EDITABLE = ['draft', 'changes_requested', 'rejected'];

    /** The sector an institution may record besides the business sectors (QA #800). */
    public const string FINANCIAL_SERVICES = 'Financial services';

    /** Directors or owners listed on the form, at most. */
    public const MAX_PEOPLE = 12;

    /** @param  int  $minimumMonthlyCommitment  the founders' institution minimum in force, in RWF */
    public function __construct(private int $minimumMonthlyCommitment = InstitutionalInvestorTerms::MIN_MONTHLY_COMMITMENT) {}

    /** @return State */
    public function empty(string $role): array
    {
        $this->role($role);
        $role = in_array($role, ['auditor', 'institution'], true) ? $role : 'business';

        return ['role' => $role, 'step' => self::STEPS[$role][0], 'answers' => ['declared' => false], 'documents' => [], 'note' => null];
    }

    /**
     * Records one step's answers and moves on. Only a reached step may be saved, and a step is saved only
     * once every answer on it is valid.
     *
     * @param  State  $state
     * @param  array<string, mixed>  $input
     * @param  list<string>  $industries
     * @param  array<string, list<string>>  $provinces  the districts of each province
     * @return State
     */
    public function save(array $state, string $status, string $step, array $input, string $today, array $industries, array $provinces): array
    {
        $this->editable($status);
        $this->reachable($state, $step);
        $errors = [];
        switch ($step) {
            case 'company':
                [$state['answers']['company'], $errors] = $this->company($input, $today, $this->sectorsFor($state['role'], $industries), $provinces);
                break;
            case 'people':
                [$state['answers']['representative'], $representative] = $this->representative($input['representative'] ?? null);
                [$state['answers']['directors'], $directors] = $this->directors($input['directors'] ?? null);
                // An institution such as a pension fund or listed bank may have no one owning 25% (QA #800):
                // it says so, and its directors stand as the people who control it.
                $state['answers']['no_major_owner'] = $state['role'] === 'institution' && filter_var($input['no_major_owner'] ?? false, FILTER_VALIDATE_BOOLEAN);
                [$state['answers']['owners'], $owners] = $this->owners($input['owners'] ?? null, $state['answers']['directors'], $state['answers']['no_major_owner']);
                $errors = [...$representative, ...$directors, ...$owners];
                break;
            case 'documents':
                $errors = $this->missingDocuments($state);
                break;
            case 'licence':
                [$state['answers']['licence'], $licence] = $this->licence($input, $today);
                $errors = [...$licence, ...$this->missingDocuments($state)];
                break;
            case 'institution':
                [$state['answers']['institution'], $errors] = $this->institution($input, $state['answers']['company']['legal_name'] ?? '');
                break;
            case 'coverage':
                [$state['answers']['address'], $address] = RwandaAddress::fromInput(is_array($input['address'] ?? null) ? $input['address'] : [], $provinces, 'address.');
                [$state['answers']['coverage'], $coverage] = $this->coverage($input, $provinces);
                $errors = [...$address, ...$coverage];
                break;
            default:
                throw new CommandRejection('APPLICATION_STEP_INVALID', 422, fieldErrors: ['step' => ['Send the application from the review step.']]);
        }
        if ($errors !== []) {
            throw new CommandRejection('APPLICATION_ANSWERS_INVALID', 422, fieldErrors: $errors);
        }
        $steps = self::STEPS[$state['role']];
        $next = $steps[(int) array_search($step, $steps, true) + 1] ?? 'review';
        if (array_search($next, $steps, true) > array_search($state['step'], $steps, true)) {
            $state['step'] = $next;
        }

        return $state;
    }

    /**
     * Files an uploaded document under its slot: a company or licence document, or one person's ID by their ID number.
     *
     * @param  State  $state
     * @return State
     */
    public function upload(array $state, string $status, string $slot, string $documentId): array
    {
        $this->editable($status);
        if (! in_array($slot, $this->slots($state), true)) {
            throw new CommandRejection('APPLICATION_SLOT_INVALID', 422, fieldErrors: ['slot' => ['Choose which document this is.']]);
        }
        $state['documents'][$slot] = $documentId;

        return $state;
    }

    /**
     * Sends the application for review once every step is complete, every required document is in, and
     * the applicant declares it true. Every answer is checked again, so a stale step can never send an
     * incomplete application.
     *
     * @param  State  $state
     * @param  list<string>  $industries
     * @param  array<string, list<string>>  $provinces  the districts of each province
     * @return State
     */
    public function submit(array $state, string $status, bool $declared, string $today, array $industries, array $provinces): array
    {
        $this->editable($status);
        $errors = $this->problems($state, $today, $industries, $provinces);
        if (! $declared) {
            $errors['declared'] = ['Confirm that everything you have given is true and complete.'];
        }
        if ($errors !== []) {
            throw new CommandRejection('APPLICATION_INCOMPLETE', 422, fieldErrors: $errors);
        }
        $state['answers']['declared'] = true;
        $state['step'] = 'review';
        $state['note'] = null;

        return $state;
    }

    /**
     * Everything still missing or invalid, checked again by Compliance's approval under its locks.
     *
     * @param  State  $state
     * @param  list<string>  $industries
     * @param  array<string, list<string>>  $provinces  the districts of each province
     * @return array<string, list<string>>
     */
    public function problems(array $state, string $today, array $industries, array $provinces): array
    {
        $answers = $state['answers'];
        if ($state['role'] !== 'auditor') {
            $institution = $state['role'] === 'institution'
                ? $this->institution($answers['institution'] ?? [], $answers['company']['legal_name'] ?? '')[1] : [];

            return [...$this->company($answers['company'] ?? [], $today, $this->sectorsFor($state['role'], $industries), $provinces)[1],
                ...$this->representative($answers['representative'] ?? null)[1],
                ...$this->people($answers['directors'] ?? null, 'directors', false)[1],
                ...$this->people($answers['owners'] ?? null, 'owners', true, $state['role'] === 'institution' && ($answers['no_major_owner'] ?? false))[1], ...$institution, ...$this->missingDocuments($state)];
        }

        return [...$this->licence($answers['licence'] ?? [], $today)[1], ...RwandaAddress::fromInput($answers['address'] ?? [], $provinces, 'address.')[1],
            ...$this->coverage($answers['coverage'] ?? [], $provinces)[1], ...$this->missingDocuments($state)];
    }

    /**
     * Every slot a document may be filed under now: the role's documents, then one ID per distinct person
     * named as a director or owner.
     *
     * @param  State  $state
     * @return list<string>
     */
    public function slots(array $state): array
    {
        if ($state['role'] === 'auditor') {
            return self::AUDITOR_DOCUMENTS;
        }

        return [...($state['role'] === 'institution' ? self::INSTITUTION_DOCUMENTS : [...self::BUSINESS_DOCUMENTS, ...self::OPTIONAL_DOCUMENTS]),
            ...array_map(fn (string $id): string => 'id:'.$id, array_map('strval', array_keys($this->peopleById($state))))];
    }

    /**
     * The distinct people named, keyed by ID number: a person who is both a director and an owner gives one ID.
     *
     * @param  State  $state
     * @return array<string, array{full_name: string, roles: list<'director'|'owner'>}>
     */
    public function peopleById(array $state): array
    {
        $people = [];
        foreach (['director' => $state['answers']['directors'] ?? [], 'owner' => $state['answers']['owners'] ?? []] as $role => $list) {
            foreach ($list as $person) {
                $people[$person['id_number']] ??= ['full_name' => $person['full_name'], 'roles' => []];
                $people[$person['id_number']]['roles'][] = $role;
            }
        }

        return $people;
    }

    /**
     * @param  State  $state
     * @return array<string, list<string>>
     */
    public function missingDocuments(array $state): array
    {
        $errors = [];
        foreach ($this->requiredDocuments($state['role']) as $slot) {
            if (($state['documents'][$slot] ?? null) === null) {
                $errors['documents.'.$slot] = ['Upload this document.'];
            }
        }
        foreach ($this->peopleById($state) as $idNumber => $person) {
            if (($state['documents']['id:'.$idNumber] ?? null) === null) {
                $errors['documents.id:'.$idNumber] = ['Upload '.$person['full_name']."'s ID."];
            }
        }

        return $errors;
    }

    /** @return list<string> */
    private function requiredDocuments(string $role): array
    {
        return match ($role) {
            'auditor' => self::AUDITOR_DOCUMENTS,
            'institution' => self::INSTITUTION_DOCUMENTS,
            default => self::BUSINESS_DOCUMENTS,
        };
    }

    /**
     * Compliance's note when it asks for more or rejects: the applicant sees it and may change their answers.
     * Asking for more also names the answers and documents to change; the applicant sees them flagged.
     *
     * @param  State  $state
     * @param  list<RevisionItem>  $items
     * @return State
     */
    public function returned(array $state, string $note, array $items = [], string $requestedAt = ''): array
    {
        $state['note'] = $note;
        $state['answers']['declared'] = false;
        if ($items !== []) {
            // What the flagged items held when they were sent back, so the applicant and Compliance can
            // both see which ones changed on the resubmission (QA 24 Nov 2026).
            $state['revision'] = ['note' => $note, 'items' => $items, 'requested_at' => $requestedAt,
                'before' => ['answers' => $state['answers'], 'documents' => $state['documents']]];
        } else {
            unset($state['revision']);
        }

        return $state;
    }

    /**
     * Every answer and document Compliance may ask the applicant to change, keyed as the form and the
     * revision request name them, with the label the applicant and the email show (24 Nov 2026).
     *
     * @param  State  $state
     * @return array<string, string>
     */
    public function revisable(array $state): array
    {
        if ($state['role'] === 'auditor') {
            return ['licence.membership_number' => 'ICPAR membership number', 'licence.licence_number' => 'Practising certificate number (the typed number)',
                'licence.expires_on' => 'Licence valid until', 'licence.firm' => 'Audit firm', 'address' => 'Registered address', 'coverage.districts' => 'Districts you accept',
                'coverage.declarations' => 'Conflicts and ethics declarations', 'documents.practising_certificate' => 'ICPAR practising certificate (the uploaded file)'];
        }
        $items = ['company.legal_name' => 'Company legal name', 'company.rdb_code' => 'RDB company code', 'company.tin' => 'TIN',
            'company.company_type' => 'Company type', 'company.incorporation_date' => 'Date of incorporation', 'company.industry' => 'Sector',
            'company.address' => 'Registered address', 'company.phone' => 'Company phone',
            'representative' => 'Your title, phone and authority', 'directors' => 'Directors', 'owners' => 'Beneficial owners',
            'documents.rdb_certificate' => 'RDB registration certificate', 'documents.tin_certificate' => 'TIN certificate',
            'documents.articles' => 'Articles of Association', 'documents.shareholder_register' => 'Shareholder register',
            'documents.board_resolution' => 'Board resolution'];
        if ($state['role'] === 'institution') {
            unset($items['documents.board_resolution']);
            $items = [...$items, 'institution.type' => 'Type of institution', 'institution.monthly_commitment' => 'Monthly distribution commitment',
                'institution.bank_account' => 'Bank account in the institution’s name', 'institution.declarations' => 'Automation declarations',
                'documents.authorisation_letter' => 'Letter authorising you to act for the institution', 'documents.bank_letter' => 'Bank confirmation letter'];
        }
        foreach ($this->peopleById($state) as $idNumber => $person) {
            $items['documents.id:'.$idNumber] = $person['full_name']."'s ID";
        }

        return $items;
    }

    public function editable(string $status): void
    {
        if (! in_array($status, self::EDITABLE, true)) {
            throw new CommandRejection('APPLICATION_NOT_EDITABLE');
        }
    }

    public function role(string $role): void
    {
        if (! in_array($role, self::ROLES, true)) {
            throw new CommandRejection('APPLICATION_ROLE_INVALID', 422, fieldErrors: ['role' => ['Choose Business, Auditor or Institutional investor.']]);
        }
    }

    /** @param State $state */
    private function reachable(array $state, string $step): void
    {
        $steps = self::STEPS[$state['role']];
        $index = array_search($step, $steps, true);
        if ($index === false) {
            throw new CommandRejection('APPLICATION_STEP_INVALID', 422, fieldErrors: ['step' => ['Choose a step of the form.']]);
        }
        if ($index > array_search($state['step'], $steps, true)) {
            throw new CommandRejection('APPLICATION_STEP_OUT_OF_ORDER');
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<string>  $industries
     * @param  array<string, list<string>>  $provinces  the districts of each province
     * @return array{0: Company, 1: array<string, list<string>>}
     */
    private function company(array $input, string $today, array $industries, array $provinces): array
    {
        $text = fn (string $key, int $max = 180): string => mb_substr(trim($this->text($input[$key] ?? '')), 0, $max);
        $digits = fn (string $key): string => (string) preg_replace('/\s+/', '', $this->text($input[$key] ?? ''));
        $company = ['legal_name' => $text('legal_name'), 'rdb_code' => $digits('rdb_code'), 'tin' => $digits('tin'),
            'company_type' => $text('company_type', 40), 'incorporation_date' => $text('incorporation_date', 10), 'industry' => $text('industry', 60),
            'province' => '', 'district' => '', 'sector' => '', 'cell' => '', 'street' => '', 'phone' => $text('phone', 32)];
        [$address, $errors] = RwandaAddress::fromInput($input, $provinces, 'company.');
        $company = [...$company, ...$address];
        if ($company['legal_name'] === '') {
            $errors['company.legal_name'] = ['Enter the company’s registered name, as on the RDB certificate.'];
        }
        if (preg_match('/^[0-9]{9}$/D', $company['rdb_code']) !== 1) {
            $errors['company.rdb_code'] = ['The RDB company code is 9 digits.'];
        }
        if (preg_match('/^[0-9]{9}$/D', $company['tin']) !== 1) {
            $errors['company.tin'] = ['The TIN is the 9-digit tax number from RRA.'];
        }
        if (! in_array($company['company_type'], self::COMPANY_TYPES, true)) {
            $errors['company.company_type'] = ['Choose the type of company.'];
        }
        if (! $this->pastDate($company['incorporation_date'], $today)) {
            $errors['company.incorporation_date'] = ['Enter the date the company was registered. It cannot be in the future.'];
        }
        if (! in_array($company['industry'], $industries, true)) {
            $errors['company.industry'] = ['Choose the sector.'];
        }
        if (! self::rwandanPhone($company['phone'])) {
            $errors['company.phone'] = ['Enter the company phone number, for example +250 788 000 000.'];
        }

        return [$company, $errors];
    }

    /** A Rwandan number: +250 or 250 then nine digits, or 0 then nine digits, spaces allowed (QA 24 Nov 2026). */
    private static function rwandanPhone(string $phone): bool
    {
        return preg_match('/^(\+?250|0)[0-9]{9}$/D', str_replace(' ', '', $phone)) === 1;
    }

    /** @return array{0: Representative, 1: array<string, list<string>>} */
    private function representative(mixed $input): array
    {
        $input = is_array($input) ? $input : [];
        $representative = ['title' => mb_substr(trim($this->text($input['title'] ?? '')), 0, 80),
            'phone' => mb_substr(trim($this->text($input['phone'] ?? '')), 0, 32),
            'authorised' => filter_var($input['authorised'] ?? false, FILTER_VALIDATE_BOOLEAN)];
        $errors = [];
        if ($representative['title'] === '') {
            $errors['representative.title'] = ['Enter your role at the company, for example Managing Director.'];
        }
        if (! self::rwandanPhone($representative['phone'])) {
            $errors['representative.phone'] = ['Enter your phone number, for example +250 788 000 000.'];
        }
        if (! $representative['authorised']) {
            $errors['representative.authorised'] = ['Confirm that you are a director or hold a board resolution to act for the company.'];
        }

        return [$representative, $errors];
    }

    /** @return array{0: list<Person>, 1: array<string, list<string>>} */
    private function directors(mixed $input): array
    {
        [$people, $errors] = $this->people($input, 'directors', false);

        return [array_map(fn (array $person): array => ['full_name' => $person['full_name'], 'id_type' => $person['id_type'],
            'id_number' => $person['id_number'], 'nationality' => $person['nationality']], $people), $errors];
    }

    /**
     * Owners, each either one of the directors (picked from them, so the same name and ID) or someone
     * who isn't a director (#761). An owner typed with a director's ID number under another name is
     * the same ID claimed by two people, so it is refused.
     *
     * @param  list<Person>  $directors
     * @return array{0: list<Owner>, 1: array<string, list<string>>}
     */
    private function owners(mixed $input, array $directors, bool $noMajorOwner = false): array
    {
        [$people, $errors] = $this->people($input, 'owners', true, $noMajorOwner);
        $names = array_column($directors, 'full_name', 'id_number');
        foreach ($people as $index => $person) {
            $director = $names[$person['id_number']] ?? null;
            if ($director !== null && mb_strtolower($director) !== mb_strtolower($person['full_name']) && ! isset($errors['owners.'.$index.'.id_number'])) {
                $errors['owners.'.$index.'.id_number'] = ['This ID number is the director '.$director.'’s. Pick them from the directors instead.'];
            }
        }

        return [array_map(fn (array $person): array => ['full_name' => $person['full_name'], 'id_type' => $person['id_type'],
            'id_number' => $person['id_number'], 'nationality' => $person['nationality'], 'ownership_pct' => $person['ownership_pct'] ?? '',
            'pep' => $person['pep'] ?? false], $people), $errors];
    }

    /**
     * The sectors a company may be recorded under: an institution may also be in financial services.
     *
     * @param  list<string>  $industries
     * @return list<string>
     */
    private function sectorsFor(string $role, array $industries): array
    {
        return $role === 'institution' ? [...$industries, self::FINANCIAL_SERVICES] : $industries;
    }

    /**
     * Directors (at least one) or owners of 25% or more (at least one, their shares adding up to no more
     * than 100%), each with a valid ID number. An institution that has said no one owns 25% or more lists no
     * owners at all.
     *
     * @return array{0: list<array{full_name: string, id_type: 'national_id'|'passport', id_number: string, nationality: string, ownership_pct?: string, pep?: bool}>, 1: array<string, list<string>>}
     */
    private function people(mixed $input, string $field, bool $owners, bool $noneRequired = false): array
    {
        $rows = is_array($input) ? array_values(array_filter($input, 'is_array')) : [];
        $errors = [];
        if ($rows !== [] && $noneRequired) {
            // A declaration that no one owns 25% and a listed 25% owner contradict each other (#801 review).
            return [[], [$field => ['You said no one owns or controls 25% or more. Remove the owners listed, or untick that box.']]];
        }
        if ($rows === [] && ! $noneRequired) {
            $errors[$field] = [$owners ? 'Add every person who owns or controls 25% or more of the company.' : 'Add at least one director.'];
        }
        if (count($rows) > self::MAX_PEOPLE) {
            $errors[$field] = ['List at most '.self::MAX_PEOPLE.' people here.'];
            $rows = array_slice($rows, 0, self::MAX_PEOPLE);
        }
        $people = [];
        $total = 0.0;
        $seen = [];
        foreach ($rows as $index => $row) {
            $type = ($row['id_type'] ?? '') === 'passport' ? 'passport' : 'national_id';
            $person = ['full_name' => mb_substr(trim($this->text($row['full_name'] ?? '')), 0, 120), 'id_type' => $type,
                'id_number' => strtoupper((string) preg_replace('/\s+/', '', $this->text($row['id_number'] ?? ''))),
                'nationality' => mb_substr(trim($this->text($row['nationality'] ?? '')), 0, 60)];
            $at = $field.'.'.$index.'.';
            if ($person['full_name'] === '') {
                $errors[$at.'full_name'] = ['Enter the full name, as on their ID.'];
            }
            if (preg_match($type === 'passport' ? '/^[A-Z0-9]{6,12}$/D' : '/^[0-9]{16}$/D', $person['id_number']) !== 1) {
                $errors[$at.'id_number'] = [$type === 'passport' ? 'A passport number is 6 to 12 letters and digits.' : 'A Rwandan national ID number is 16 digits.'];
            } elseif (isset($seen[$person['id_number']])) {
                $errors[$at.'id_number'] = ['This person is already listed.'];
            }
            $seen[$person['id_number']] = true;
            if ($person['nationality'] === '') {
                $errors[$at.'nationality'] = ['Enter their nationality.'];
            }
            if ($owners) {
                $share = trim($this->text($row['ownership_pct'] ?? ''));
                $person['ownership_pct'] = mb_substr($share, 0, 8);
                $person['pep'] = filter_var($row['pep'] ?? false, FILTER_VALIDATE_BOOLEAN);
                // Plain digits with an optional decimal part, the grammar the form accepts: is_numeric
                // would also take 3e1 or +25, which the form flags (#775 review).
                if (preg_match('/^\d+(\.\d+)?$/D', $share) !== 1 || (float) $share < 25 || (float) $share > 100) {
                    $errors[$at.'ownership_pct'] = ['List owners of 25% or more. Enter their share between 25 and 100.'];
                } else {
                    $total += (float) $share;
                }
            }
            $people[] = $person;
        }
        if ($owners && $total > 100.0001) {
            $errors[$field] = ['The shares add up to more than 100%.'];
        }

        return [$people, $errors];
    }

    /**
     * The ICPAR membership, the practising certificate's number and expiry (not passed), and the firm if any.
     *
     * @param  array<string, mixed>  $input
     * @return array{0: Licence, 1: array<string, list<string>>}
     */
    private function licence(array $input, string $today): array
    {
        $text = fn (string $key, int $max): string => mb_substr(trim($this->text($input[$key] ?? '')), 0, $max);
        $licence = ['membership_number' => $text('membership_number', 40), 'licence_number' => $text('licence_number', 32),
            'expires_on' => $text('expires_on', 10), 'firm' => $text('firm', 120)];
        $errors = [];
        foreach (['membership_number' => 'Enter your ICPAR membership number.', 'licence_number' => 'Enter the number on your practising certificate.'] as $key => $message) {
            if ($licence[$key] === '' || preg_match('/[\p{Cc}\p{Cf}]/u', $licence[$key]) === 1) {
                $errors['licence.'.$key] = [$message];
            }
        }
        if (! $this->validDate($licence['expires_on']) || $licence['expires_on'] < $today) {
            $errors['licence.expires_on'] = ['Enter the expiry date on a current practising certificate.'];
        }

        return [$licence, $errors];
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, list<string>>  $provinces  the districts of each province
     * @return array{0: Coverage, 1: array<string, list<string>>}
     */
    private function coverage(array $input, array $provinces): array
    {
        $districts = array_merge(...array_values($provinces));
        $chosen = is_array($input['districts'] ?? null) ? array_values(array_unique(array_filter($input['districts'], 'is_string'))) : [];
        $coverage = ['districts' => array_values(array_intersect($chosen, $districts)),
            'no_conflicts' => filter_var($input['no_conflicts'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'will_declare' => filter_var($input['will_declare'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'ethics' => filter_var($input['ethics'] ?? false, FILTER_VALIDATE_BOOLEAN)];
        $errors = [];
        if ($coverage['districts'] === [] || count($coverage['districts']) !== count($chosen)) {
            $errors['coverage.districts'] = ['Choose the districts you accept audits in.'];
        }
        foreach (['no_conflicts' => 'Confirm you have no conflict with a business raising on Rozine.',
            'will_declare' => 'Confirm you will declare any new conflict before an audit.',
            'ethics' => 'Agree to the Auditor terms and the ICPAR code of ethics.'] as $key => $message) {
            if (! $coverage[$key]) {
                $errors['coverage.'.$key] = [$message];
            }
        }

        return [$coverage, $errors];
    }

    /**
     * The institution's type, its monthly distribution commitment (at least RWF 30,000,000), the bank
     * account in its own registered name, and its two declarations: it automates the distribution, and
     * it sets its own rules because Rozine recommends no business (Robert, 26 Nov 2026).
     *
     * @param  array<string, mixed>  $input
     * @return array{0: Institution, 1: array<string, list<string>>}
     */
    private function institution(array $input, string $legalName): array
    {
        $text = fn (string $key, int $max): string => mb_substr(trim($this->text($input[$key] ?? '')), 0, $max);
        $institution = ['type' => $text('type', 40), 'monthly_commitment' => (string) preg_replace('/[\s,]+/', '', $text('monthly_commitment', 20)),
            'bank_name' => $text('bank_name', 80), 'account_name' => $text('account_name', 180),
            'account_number' => (string) preg_replace('/[\s-]+/', '', $text('account_number', 40)),
            'automates' => filter_var($input['automates'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'own_rules' => filter_var($input['own_rules'] ?? false, FILTER_VALIDATE_BOOLEAN)];
        $errors = [];
        if (! in_array($institution['type'], InstitutionalInvestorTerms::TYPES, true)) {
            $errors['institution.type'] = ['Choose the type of institution.'];
        }
        $amount = $institution['monthly_commitment'];
        if (preg_match('/^[1-9][0-9]{0,12}$/D', $amount) !== 1 || (int) $amount < $this->minimumMonthlyCommitment
            || (int) $amount > InstitutionalInvestorTerms::MAX_MONTHLY_COMMITMENT) {
            $errors['institution.monthly_commitment'] = [sprintf('Institutions commit to distributing at least RWF %s a month.', number_format($this->minimumMonthlyCommitment))];
        }
        if ($institution['bank_name'] === '') {
            $errors['institution.bank_name'] = ['Enter the bank that holds the institution’s account.'];
        }
        if (! InstitutionalInvestorTerms::accountNameMatches($institution['account_name'], $legalName)) {
            $errors['institution.account_name'] = ['The account must be in the institution’s registered name, exactly as on the RDB certificate.'];
        }
        if (preg_match('/^[0-9]{6,20}$/D', $institution['account_number']) !== 1) {
            $errors['institution.account_number'] = ['Enter the account number, 6 to 20 digits.'];
        }
        if (! $institution['automates']) {
            $errors['institution.automates'] = ['Confirm the institution will distribute this amount every month through Auto-Deploy.'];
        }
        if (! $institution['own_rules']) {
            $errors['institution.own_rules'] = ['Confirm the institution sets its own investing rules. Rozine does not recommend businesses.'];
        }

        return [$institution, $errors];
    }

    private function pastDate(string $date, string $today): bool
    {
        return $this->validDate($date) && $date <= $today && $date >= '1900-01-01';
    }

    private function validDate(string $date): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date) === 1
            && checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4));
    }

    private function text(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
