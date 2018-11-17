<?php
declare(strict_types = 1);

namespace ISTSI\Controllers;

use ISTSI\Helpers\DateTime;
use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class Front
{
    protected $c;

    public function __construct(ContainerInterface $c)
    {
        $this->c = $c;
    }

    public function showHome(Request $request, Response $response, $args)
    {
        $settingsProgram = $this->c->get('settings')['program'];

        if (DateTime::isBefore($settingsProgram['period']['start'])) {
            $templateName = 'home.teaser';
            $templateArgs = [
                'programName' => $settingsProgram['name'],
                'programYear' => $settingsProgram['year'],
                'email'       => $settingsProgram['email'],
                'facebook'    => $settingsProgram['facebook'],
                'societies'   => $settingsProgram['societies']
            ];
        } else {
            $database = $this->c->get('database');
            $session = $this->c->get('session');

            if ($session->getToken() === null) {
                $session->setToken();
            }

            $companyMapper = $database->mapper('\ISTSI\Database\Entities\Company');
            $courseMapper = $database->mapper('\ISTSI\Database\Entities\Course');
            $proposalMapper = $database->mapper('\ISTSI\Database\Entities\Proposal');

            $proposalsQuery = $proposalMapper->all();

            $noCompanies = count($companyMapper->all());
            $noProposals = $noVacancies = 0;

            $proposals = $proposalsQuery->toArray();

            foreach ($proposalsQuery as $key => $proposal) {
                $noProposals++;
                $noVacancies += $proposals[$key]['vacancies'];
                $proposals[$key]['company_id'] = $companyMapper->get($proposal->company_id)->name;
                $proposals[$key]['courses'] = array_column(
                    $proposal->relation('courses')->getIterator()->toArray(),
                    'acronym'
                );
            }

            $templateName = 'home.full';
            $templateArgs = [
                'programName'   => $settingsProgram['name'],
                'programYear'   => $settingsProgram['year'],
                'email'         => $settingsProgram['email'],
                'facebook'      => $settingsProgram['facebook'],
                'societies'     => $settingsProgram['societies'],
                'termsPath'     => $settingsProgram['termsPath'],
                'courses'       => $courseMapper->all()->toArray(),
                'proposals'     => $proposals,
                'noCompanies'   => $noCompanies,
                'noProposals'   => $noProposals,
                'noVacancies'   => $noVacancies,
                'periodStart'   => $settingsProgram['period']['start'],
                'periodEnd'     => $settingsProgram['period']['end'],
                'betweenPeriod' => DateTime::isBetween(
                    $settingsProgram['period']['start'],
                    $settingsProgram['period']['end']
                ),
                'token'         => $session->getToken()
            ];
        }
        return $this->c->get('renderer')->render($response, 'front/' . $templateName . '.twig', $templateArgs);
    }
}
