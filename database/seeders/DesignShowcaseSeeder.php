<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Disbursement\SyntheticDisbursementGuard;
use App\Application\Environment\EnvironmentIsolation;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Wallet\SyntheticWalletGuard;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;
use LogicException;
use Tests\Support\DesignShowcaseFixture;

/**
 * Opt-in local showcase data: fills an empty local database so each app reads like the claude.ai/design
 * Suite, using the design's own sample names and requested figures. Every write goes through the real
 * application actions, the synthetic hooks behind `local:wallet` and `local:disbursement`, and the
 * acceptance fixtures (`Tests\Support\DesignShowcaseFixture`); quotes, ratings, balances, seals and
 * progress are what the domain computes. Requires Composer's development autoloader. Not called from
 * DatabaseSeeder; run it with `php artisan db:seed --class=DesignShowcaseSeeder`.
 *
 * @phpstan-import-type Business from DesignShowcaseFixture
 * @phpstan-import-type Raise from DesignShowcaseFixture
 *
 * @phpstan-type Persona array{label: string, name: string, email: string, password: string, totp_secret: string|null, opens: string}
 */
class DesignShowcaseSeeder extends Seeder
{
    public const string PASSWORD = 'Rozine-Showcase-local-only-42!';

    /** Relative to storage_path(); `storage/app` is ignored by git. */
    public const string ACCOUNTS = 'app/design-showcase-accounts.json';

    /** The signed-in Investor's available balance after his purchases, as the design's wallet shows it. */
    public const string INVESTOR_AVAILABLE = '4200000';

    /** Created last, so its presence means a complete pack. */
    private const string COMPLETE_MARKER = 'a.diane@example.test';

    private const int LOCK = 962031;

    /** @var array<string, Persona> */
    private array $personas = [];

    public function run(): void
    {
        $personas = $this->prepare();
        $this->command->info('Design showcase ready. Sign-ins: '.storage_path(self::ACCOUNTS));
        $this->command->table(['Persona', 'Email', 'MFA', 'Opens'], array_map(fn (array $persona): array => [$persona['label'], $persona['email'],
            $persona['totp_secret'] === null ? 'No' : 'Authenticator', $persona['opens']], array_values($personas)));
    }

    /**
     * Local, testing or an enabled demo (`EnvironmentIsolation::canSeed()`), and only where the synthetic
     * money hooks are bound, which also rules out demo, staging and production; a local PostgreSQL only.
     */
    public function assertAllowed(): void
    {
        $isolation = app(EnvironmentIsolation::class);
        if (! $isolation->canSeed()) {
            throw new LogicException('DESIGN_SHOWCASE_SEED_DENIED: seeding is not allowed in the '.$isolation->profile().' profile.');
        }
        if (! app(SyntheticWalletGuard::class)->allowed() || ! app(SyntheticDisbursementGuard::class)->allowed()) {
            throw new LogicException('DESIGN_SHOWCASE_SYNTHETIC_MONEY_UNAVAILABLE: deposits and disbursements are synthetic only in local and testing.');
        }
        $config = config()->array('database.connections.'.config()->string('database.default'));
        if (($config['driver'] ?? null) !== 'pgsql' || ! in_array($config['host'] ?? null, ['127.0.0.1', 'localhost', '::1'], true)
            || ! empty($config['url']) || isset($config['read']) || isset($config['write'])) {
            throw new LogicException('DESIGN_SHOWCASE_LOCAL_DATABASE_REQUIRED: use a local PostgreSQL database.');
        }
        if (! class_exists(DesignShowcaseFixture::class)) {
            throw new LogicException('DESIGN_SHOWCASE_DEV_AUTOLOADER_REQUIRED: install Composer development dependencies.');
        }
    }

    /** @return array<string, Persona> */
    public function prepare(): array
    {
        $this->assertAllowed();
        DB::select('SELECT pg_advisory_lock(?)', [self::LOCK]);
        try {
            if (User::query()->where('email', self::COMPLETE_MARKER)->exists()) {
                return $this->writeAccounts($this->existingPersonas());
            }
            if (User::query()->whereIn('email', array_column(self::PERSONAS, 0))->exists()) {
                throw new LogicException('DESIGN_SHOWCASE_INCOMPLETE: an earlier run stopped part-way. Reset with `php artisan migrate:fresh` and seed again.');
            }
            $this->seed();

            return $this->writeAccounts($this->personas);
        } finally {
            DB::select('SELECT pg_advisory_unlock(?)', [self::LOCK]);
        }
    }

    private function seed(): void
    {
        $compliance = $this->staff('compliance', 'Grace Kalisa', 'grace.kalisa@example.test', ['compliance']);
        $operations = $this->staff('operations', 'Eric Ndoli', 'eric.ndoli@example.test', ['approver', 'treasury']);
        $suspended = DesignShowcaseFixture::staff('Jean-Paul M.', 'jean-paul.m@example.test', self::PASSWORD, ['approver'])['user'];
        app(ConfigureStaffAccess::class)->handle($suspended->id, false, 'Suspended pending conduct review.', (string) Str::uuid());
        DesignShowcaseFixture::catalogs($compliance);

        $auditor = fn (string $name, string $email, string $licence, string $expires, bool $approved = true): User => DesignShowcaseFixture::auditor($name, $email,
            self::PASSWORD, $licence, $expires, $compliance, $approved)['user'];
        ['user' => $diane, 'secret' => $secret] = DesignShowcaseFixture::auditor('Diane Uwase', 'diane.uwase@example.test', self::PASSWORD,
            'ICPAR/P-2026/0481', '2027-03-31', $compliance, true);
        $this->persona('auditor', $diane, $secret, route('auditor.home', [], false));
        $kamanzi = $auditor('Eric Kamanzi', 'eric.kamanzi@example.test', 'ICPAR-2020-0731', '2026-11-30');
        $karangwa = $auditor('Marie Karangwa', 'marie.karangwa@example.test', 'ICPAR/P-2026/739', '2027-06-30');
        $niyonsaba = $auditor('Kevin Niyonsaba', 'kevin.niyonsaba@example.test', 'ICPAR/P-2026/830', '2027-08-31');
        $okello = $auditor('Yves Okello', 'yves.okello@example.test', 'ICPAR/P-2026/752', '2027-05-31');
        $auditor('Aline Ishimwe', 'aline.ishimwe@example.test', 'ICPAR-2022-1108', '2028-01-31', false);
        $auditor('Patrick Niyonzima', 'patrick.niyonzima@example.test', 'ICPAR-2021-0955', '2027-09-30', false);

        $investors = $this->investors();
        // Robert Mugisha is the design's signed-in Investor and GreenLeaf Agro's CEO: one account, both apps.
        $robert = $investors['robert'] ?? throw new LogicException('DESIGN_SHOWCASE_UNKNOWN_INVESTOR: robert');
        $this->persona('investor', $robert, null, route('investor.deals', [], false));

        // Huye Supply's Coffee Inventory note: fully committed and its funding locked.
        $huyeSupply = DesignShowcaseFixture::business(self::HUYE_SUPPLY, self::PASSWORD, $operations, $okello);
        $coffee = DesignShowcaseFixture::publish($huyeSupply, DesignShowcaseFixture::application($huyeSupply, self::HUYE_SUPPLY_RAISE, $operations, $okello,
            $compliance, 'published'), $operations);
        DesignShowcaseFixture::commit($coffee, $this->purchases($investors, [['marie', '2400'], ['patrick', '1440'], ['claudine', '200'], ['peace', '600'], ['robert', '160']]));
        DesignShowcaseFixture::fund($coffee);

        // The other live raises on the Deals deck, each audited by its Audit Partner in the design. The deck
        // lists the newest listing first, so GreenLeaf's Cold-Chain Hub is published last.
        foreach ([[self::KIGALI_MOTORS, self::KIGALI_MOTORS_RAISE, $niyonsaba, [['aline', '3000'], ['diane', '2781'], ['robert', '100']]],
            [self::RUGALI, self::RUGALI_RAISE, $karangwa, [['claudine', '200'], ['olivier', '1024'], ['robert', '300']]],
            [self::UMUGANDA, self::UMUGANDA_RAISE, $kamanzi, [['jean', '2000'], ['chantal', '1500'], ['peace', '751'], ['robert', '200']]]] as [$spec, $raise, $partner, $purchases]) {
            $business = DesignShowcaseFixture::business($spec, self::PASSWORD, $operations, $partner);
            DesignShowcaseFixture::commit(DesignShowcaseFixture::publish($business, DesignShowcaseFixture::application($business, $raise, $operations, $partner,
                $compliance, 'published'), $operations), $this->purchases($investors, $purchases));
        }

        // Diane Uwase's own book: GreenLeaf's monthly audit is co-signed and its Cold-Chain Hub raise is live;
        // Sebeya's monthly report waits on its co-signature; Huye Motors is offered to her (offers do not
        // count towards her three active jobs) before she accepts Murakoze Tech's flash audit.
        $greenleaf = DesignShowcaseFixture::business(self::GREENLEAF, self::PASSWORD, $operations, $diane);
        $this->persona('business_owner', $greenleaf['users'][0], null, route('business.home', [], false));
        $coldChain = DesignShowcaseFixture::publish($greenleaf, DesignShowcaseFixture::application($greenleaf, self::GREENLEAF_RAISE, $operations, $diane,
            $compliance, 'published', 'routine'), $operations);
        DesignShowcaseFixture::commit($coldChain, $this->purchases($investors, [['claudine', '80'], ['jean', '1000'], ['marie', '700'], ['patrick', '600']]));

        $sebeya = DesignShowcaseFixture::business(self::SEBEYA, self::PASSWORD, $operations, $diane);
        $this->persona('business_cosign', $sebeya['users'][0], null, route('business.home', [], false));
        DesignShowcaseFixture::application($sebeya, self::SEBEYA_RAISE, $operations, $diane, $compliance, 'sealed', 'routine');
        $huyeMotors = DesignShowcaseFixture::business(self::HUYE_MOTORS, self::PASSWORD, $operations, $diane);
        DesignShowcaseFixture::application($huyeMotors, self::HUYE_MOTORS_RAISE, $operations, $diane, $compliance, 'offered');
        $murakoze = DesignShowcaseFixture::business(self::MURAKOZE, self::PASSWORD, $operations, $diane);
        DesignShowcaseFixture::application($murakoze, self::MURAKOZE_RAISE, $operations, $diane, $compliance, 'in_progress');

        // Identity submissions waiting on Compliance, the design's KYC reminders.
        $this->persona('investor_pending', DesignShowcaseFixture::pendingInvestor('Patrick Gatera', 'patrick.gatera@example.test', self::PASSWORD,
            '14/3/1991', '1 1991 8 0045123 7 21'), null, route('investor.verification', [], false));
        DesignShowcaseFixture::pendingInvestor('Samuel Mugisha', 'samuel.mugisha@example.test', self::PASSWORD, '2/11/1987', '1 1987 8 0031876 4 12');
        DesignShowcaseFixture::pendingInvestor('Peace Bizimana', 'peace.bizimana@example.test', self::PASSWORD, '21/6/1995', '1 1995 7 0067234 1 09');
        DesignShowcaseFixture::pulse(self::PULSE_BUSINESSES, self::PULSE_PLEDGES);
        DesignShowcaseFixture::disbursement(4);
        $this->staff('superadmin', 'A. Diane', self::COMPLETE_MARKER, ['superadmin']);
    }

    /** @return array<string, User> */
    private function investors(): array
    {
        $investors = [];
        foreach (self::INVESTORS as $key => [$name, $email, $deposits]) {
            $investors[$key] = DesignShowcaseFixture::investor($name, $email, self::PASSWORD, $deposits);
        }

        return $investors;
    }

    /**
     * @param  array<string, User>  $investors
     * @param  list<array{string, string}>  $purchases  investor key and units
     * @return list<array{User, string}>
     */
    private function purchases(array $investors, array $purchases): array
    {
        return array_map(fn (array $purchase): array => [$investors[$purchase[0]] ?? throw new LogicException('DESIGN_SHOWCASE_UNKNOWN_INVESTOR: '.$purchase[0]),
            $purchase[1]], $purchases);
    }

    /** @param list<string> $roles */
    private function staff(string $label, string $name, string $email, array $roles): User
    {
        ['user' => $user, 'secret' => $secret] = DesignShowcaseFixture::staff($name, $email, self::PASSWORD, $roles);
        $this->persona($label, $user, $secret, route('staff.dashboard', [], false));

        return $user;
    }

    private function persona(string $label, User $user, ?string $secret, string $opens): void
    {
        $this->personas[$label] = ['label' => $label, 'name' => $user->name, 'email' => $user->email, 'password' => self::PASSWORD,
            'totp_secret' => $secret, 'opens' => $opens];
    }

    /** @return array<string, Persona> */
    private function existingPersonas(): array
    {
        $personas = [];
        foreach (self::PERSONAS as $label => [$email, $opens]) {
            $user = User::query()->where('email', $email)->sole();
            $secret = $user->two_factor_secret === null ? null : decrypt($user->two_factor_secret);
            $personas[$label] = ['label' => $label, 'name' => $user->name, 'email' => $email, 'password' => self::PASSWORD,
                'totp_secret' => is_string($secret) ? $secret : null, 'opens' => route($opens, [], false)];
        }

        return $personas;
    }

    /**
     * @param  array<string, Persona>  $personas
     * @return array<string, Persona>
     */
    private function writeAccounts(array $personas): array
    {
        $ordered = [];
        foreach (array_keys(self::PERSONAS) as $label) {
            $ordered[$label] = $personas[$label] ?? throw new LogicException('DESIGN_SHOWCASE_PERSONA_MISSING: '.$label);
        }
        try {
            $json = json_encode(['generated_at' => CarbonImmutable::now('UTC')->toIso8601String(), 'login' => route('login'),
                'note' => 'Synthetic local-only accounts from DesignShowcaseSeeder. Never reuse these credentials anywhere else.',
                'personas' => $ordered], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new LogicException('DESIGN_SHOWCASE_ACCOUNTS_UNWRITABLE');
        }
        $path = storage_path(self::ACCOUNTS);
        if (file_put_contents($path, $json."\n") === false || ! chmod($path, 0600)) {
            throw new LogicException('DESIGN_SHOWCASE_ACCOUNTS_UNWRITABLE');
        }

        return $ordered;
    }

    /** @var array<string, array{string, string}> persona => [email, landing route] */
    private const array PERSONAS = [
        'investor' => ['robert.mugisha@example.test', 'investor.deals'],
        'investor_pending' => ['patrick.gatera@example.test', 'investor.verification'],
        'business_owner' => ['robert.mugisha@example.test', 'business.home'],
        'business_cosign' => ['emmanuel.nkurunziza@example.test', 'business.home'],
        'auditor' => ['diane.uwase@example.test', 'auditor.home'],
        'compliance' => ['grace.kalisa@example.test', 'staff.dashboard'],
        'operations' => ['eric.ndoli@example.test', 'staff.dashboard'],
        'superadmin' => [self::COMPLETE_MARKER, 'staff.dashboard'],
    ];

    /**
     * Verified Investors and the deposits that fund their purchases; Robert Mugisha keeps RWF 4,200,000 available.
     *
     * @var array<string, array{string, string, list<string>}>
     */
    private const array INVESTORS = [
        'robert' => ['Robert Mugisha', 'robert.mugisha@example.test', ['6000000', '2000000']],
        'claudine' => ['Claudine Uwase', 'claudine.uwase@example.test', ['5000000', '3800000']],
        'jean' => ['Jean Kamanzi', 'jean.kamanzi@example.test', ['15500000']],
        'marie' => ['Marie Niyonsaba', 'marie.niyonsaba@example.test', ['16000000']],
        'patrick' => ['Patrick Habimana', 'patrick.habimana@example.test', ['11500000']],
        'chantal' => ['Chantal Uwase', 'chantal.uwase@example.test', ['8000000']],
        'peace' => ['Peace Habimana', 'peace.habimana@example.test', ['8000000']],
        'olivier' => ['Olivier Kamanzi', 'olivier.kamanzi@example.test', ['7000000']],
        'aline' => ['Aline Keza', 'aline.keza@example.test', ['15500000']],
        'diane' => ['Diane Mukamana', 'diane.mukamana@example.test', ['15000000']],
    ];

    /** @var Business */
    private const array GREENLEAF = ['name' => 'GreenLeaf Agro', 'code' => 'RDB-2017-618064', 'industry' => 'FMCG / Perishables', 'district' => 'Gasabo',
        'established_year' => 2017, 'inflow' => '32000000', 'outflow' => '24000000', 'people' => [
            ['name' => 'Robert Mugisha', 'email' => 'robert.mugisha@example.test'],
            ['name' => 'Aline Uwase', 'email' => 'aline.uwase@example.test'],
            ['name' => 'Jean Bosco', 'email' => 'jean.bosco@example.test'],
        ]];

    /** @var Raise */
    private const array GREENLEAF_RAISE = ['title' => 'Cold-Chain Hub', 'target' => '18000000', 'term_months' => 5, 'use_of_funds' => ['expansion', 'equipment'],
        'story' => "GreenLeaf Agro supplies maize, beans and horticulture across the Eastern Province.\nThe raise expands cold-storage capacity to double export volume."];

    /** @var Business */
    private const array SEBEYA = ['name' => 'Sebeya Logistics', 'code' => 'RDB-2018-230738', 'industry' => 'Wholesale / Commodities', 'district' => 'Musanze',
        'established_year' => 2018, 'inflow' => '24000000', 'outflow' => '15000000', 'people' => [
            ['name' => 'Emmanuel Nkurunziza', 'email' => 'emmanuel.nkurunziza@example.test'],
        ]];

    /** @var Raise */
    private const array SEBEYA_RAISE = ['title' => 'Warehouse Expansion', 'target' => '20000000', 'term_months' => 6, 'use_of_funds' => ['expansion'],
        'story' => 'A second bonded warehouse on the Musanze–Kigali route.'];

    /** @var Business */
    private const array HUYE_MOTORS = ['name' => 'Huye Motors', 'code' => 'RDB-2020-979448', 'industry' => 'Health', 'district' => 'Gasabo',
        'established_year' => 2020, 'inflow' => '50000000', 'outflow' => '35000000', 'people' => [
            ['name' => 'Innocent Mutabazi', 'email' => 'innocent.mutabazi@example.test'],
        ]];

    /** @var Raise */
    private const array HUYE_MOTORS_RAISE = ['title' => 'Energy Equipment', 'target' => '51200000', 'term_months' => 6, 'use_of_funds' => ['equipment'],
        'story' => 'Diagnostic equipment for the Gasabo workshop.'];

    /** @var Business */
    private const array MURAKOZE = ['name' => 'Murakoze Tech', 'code' => 'RDB-2019-949206', 'industry' => 'Retail / Electronics', 'district' => 'Kicukiro',
        'established_year' => 2019, 'inflow' => '28000000', 'outflow' => '19000000', 'people' => [
            ['name' => 'Olivier Mugabo', 'email' => 'olivier.mugabo@example.test'],
        ]];

    /** @var Raise */
    private const array MURAKOZE_RAISE = ['title' => 'New Yoghurt Plant', 'target' => '9000000', 'term_months' => 4, 'use_of_funds' => ['equipment'],
        'story' => 'A small yoghurt line beside the Kicukiro shop.'];

    /** @var Business */
    private const array UMUGANDA = ['name' => 'Umuganda Supply', 'code' => 'RDB-2022-580512', 'industry' => 'Manufacturing', 'district' => 'Karongi',
        'established_year' => 2019, 'inflow' => '40000000', 'outflow' => '27000000', 'people' => [
            ['name' => 'Alice Mukeshimana', 'email' => 'alice.mukeshimana@example.test'],
        ]];

    /** @var Raise */
    private const array UMUGANDA_RAISE = ['title' => 'Supply Equipment', 'target' => '33500000', 'term_months' => 5, 'use_of_funds' => ['equipment'],
        'story' => 'New packaging equipment for the Karongi plant.'];

    /** @var Business */
    private const array RUGALI = ['name' => 'Rugali Textiles', 'code' => 'RDB-2022-694215', 'industry' => 'Retail', 'district' => 'Kicukiro',
        'established_year' => 2020, 'inflow' => '36000000', 'outflow' => '25000000', 'people' => [
            ['name' => 'Fabrice Habimana', 'email' => 'fabrice.habimana@example.test'],
        ]];

    /** @var Raise */
    private const array RUGALI_RAISE = ['title' => 'Foods Working Capital', 'target' => '35000000', 'term_months' => 6, 'use_of_funds' => ['working_capital'],
        'story' => 'Working capital for the Kicukiro stores ahead of the festive season.'];

    /** @var Business */
    private const array KIGALI_MOTORS = ['name' => 'Kigali Motors', 'code' => 'RDB-2021-919881', 'industry' => 'Hospitality', 'district' => 'Gasabo',
        'established_year' => 2016, 'inflow' => '60000000', 'outflow' => '49000000', 'people' => [
            ['name' => 'Eric Ntwari', 'email' => 'eric.ntwari@example.test'],
        ]];

    /** @var Raise */
    private const array KIGALI_MOTORS_RAISE = ['title' => 'Freight Equipment', 'target' => '35500000', 'term_months' => 6, 'use_of_funds' => ['equipment'],
        'story' => 'Two refrigerated vans for hotel deliveries.'];

    /** @var Business */
    private const array HUYE_SUPPLY = ['name' => 'Huye Supply', 'code' => 'RDB-2016-196807', 'industry' => 'Retail', 'district' => 'Kicukiro',
        'established_year' => 2017, 'inflow' => '30000000', 'outflow' => '19000000', 'people' => [
            ['name' => 'Josiane Uwimana', 'email' => 'josiane.uwimana@example.test'],
        ]];

    /** @var Raise */
    private const array HUYE_SUPPLY_RAISE = ['title' => 'Coffee Inventory', 'target' => '24000000', 'term_months' => 4, 'use_of_funds' => ['inventory'],
        'story' => 'Green-coffee inventory for the export season.'];

    /**
     * Pulse's sample pool, with revenue, costs and registration year chosen so Pulse's own score lands on the
     * design's strength score: name, sector, province, district, annual revenue, annual costs, registered, term.
     *
     * @var list<array{string, string, string, string, int, int, int, int}>
     */
    private const array PULSE_BUSINESSES = [
        ['GreenLeaf Agro', 'Agriculture', 'Kigali City', 'Gasabo', 384000000, 233100000, 2017, 6],
        ['Sebeya Logistics', 'Logistics', 'Northern', 'Musanze', 288000000, 253700000, 2018, 5],
        ['Volt Energy', 'Energy', 'Kigali City', 'Kicukiro', 96000000, 90300000, 2021, 4],
        ['Kivu Coffee Co.', 'Agriculture', 'Western', 'Rubavu', 180000000, 141400000, 2016, 6],
        ['Mozaic Breweries', 'Manufacturing', 'Kigali City', 'Nyarugenge', 150000000, 133900000, 2019, 4],
        ['Akagera Foods', 'Retail & trade', 'Eastern', 'Kayonza', 240000000, 182900000, 2015, 5],
        ['Nyungwe Tea Ltd', 'Agriculture', 'Southern', 'Nyamagabe', 120000000, 111400000, 2020, 4],
        ['Rwanda Ready Mix', 'Other', 'Kigali City', 'Gasabo', 420000000, 350000000, 2014, 6],
        ['Ubwiza Cosmetics', 'Retail & trade', 'Kigali City', 'Kicukiro', 84000000, 74000000, 2020, 5],
        ['Huye Textiles', 'Manufacturing', 'Southern', 'Huye', 72000000, 68600000, 2022, 4],
        ['Bralirwa Depot Co.', 'Logistics', 'Western', 'Rubavu', 210000000, 180000000, 2016, 6],
        ['Isoko Hardware', 'Retail & trade', 'Southern', 'Muhanga', 132000000, 117900000, 2019, 5],
        ['Musanze Dairy', 'Agriculture', 'Northern', 'Musanze', 168000000, 124000000, 2015, 6],
        ['Karongi Poultry', 'Agriculture', 'Western', 'Karongi', 60000000, 54300000, 2022, 4],
        ['Twigire Transport', 'Logistics', 'Eastern', 'Rwamagana', 108000000, 97700000, 2020, 5],
        ['Gasabo Pharmacy', 'Services', 'Kigali City', 'Gasabo', 156000000, 107700000, 2014, 6],
        ['Rubavu Fisheries', 'Agriculture', 'Western', 'Rubavu', 90000000, 78200000, 2021, 5],
        ['Cyangugu Millers', 'Manufacturing', 'Western', 'Rusizi', 204000000, 177300000, 2017, 4],
    ];

    /** @var list<array{string, string, string, int}> name, province, district, pledge */
    private const array PULSE_PLEDGES = [
        ['Patrick Kayitare', 'Kigali City', 'Gasabo', 500000], ['Olivier Umutoni', 'Kigali City', 'Kicukiro', 1500000],
        ['Peace Kamanzi', 'Southern', 'Huye', 250000], ['Yves Bizimana', 'Northern', 'Musanze', 2000000],
        ['Eric Mugisha', 'Kigali City', 'Nyarugenge', 750000], ['Jean Bizimana', 'Eastern', 'Rwamagana', 300000],
        ['Aline Bizimana', 'Western', 'Rubavu', 5000000], ['Emmanuel Umutoni', 'Kigali City', 'Gasabo', 1000000],
        ['Yves Mugisha', 'Southern', 'Muhanga', 100000], ['Sandrine Okello', 'Kigali City', 'Kicukiro', 500000],
        ['David Uwera', 'Eastern', 'Kayonza', 2500000], ['Linda Mugisha', 'Western', 'Rusizi', 400000],
    ];
}
