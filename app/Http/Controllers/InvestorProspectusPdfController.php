<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Business\GetInvestorProspectus;
use App\Http\Resources\InvestorProspectusResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * The signed prospectus behind a deal as a private, never-cached PDF attachment
 * (`investor.deals.prospectus_pdf`, Download PDF on the reader), laid out from exactly what the
 * reader shows. The evidence documents stay out of it.
 */
class InvestorProspectusPdfController extends Controller
{
    public function __invoke(Request $request, GetInvestorProspectus $action, string $campaign): Response
    {
        /** @var array{deal: array{name: string}, prospectus: array{version: int, fingerprint: string, signed_at: string, signed_by: string}} $page */
        $page = (new InvestorProspectusResource($action->handle((int) $request->user()?->getAuthIdentifier(), $campaign)))->resolve($request);
        $pdf = Pdf::loadView('documents.investor-prospectus', $page)->setPaper('a4')->setOption('isFontSubsettingEnabled', true);
        $response = new Response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $filename = 'rozine-prospectus-'.(Str::slug($page['deal']['name']) ?: 'deal').'-v'.$page['prospectus']['version'].'.pdf';
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition('attachment', $filename));

        return $response;
    }
}
