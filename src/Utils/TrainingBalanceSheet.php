<?php

namespace App\Utils;

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 09/01/2015
 * Time: 14:17.
 */

use App\Entity\Back\Session;
use App\Entity\Core\AbstractInscription;
use App\Entity\Core\AbstractTrainee;
use App\Entity\Core\AbstractTrainer;
use App\Entity\Core\AbstractMaterial;
use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractTraining;

use App\Entity\Term\PresenceStatus;
use App\Entity\Term\PublicType;
use App\Entity\Session\ParticipantsSummary;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Class TrainingBalanceSheet.
 */
class TrainingBalanceSheet
{
    /**
     * @var AbstractTraining
     */
    private $training;

    /** @var Container */
    private $container;

    /**
     * PHPExcel object.
     *
     * @var
     */
    private $phpExcelObject;

    private $spreadsheet;

    /**
     * @param $training
     */
    public function __construct($training, $container)
    {
        $this->training = $training;
        $this->container = $container;
	$this->spreadsheet = new Spreadsheet();
    }

    /**
     * Builds balance sheet and returns the file through response.
     *
     * @return Response
     */
    public function getResponse()
    {
        $this->buildBalanceSheet();

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($this->spreadsheet, 'Xls');
	$response =  new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=fiche_'.strtolower(Container::camelize($this->training->getTypeLabel())).'_'.$this->training->getId().'.xls');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');

        return $response;
    }

    /**
     * Computes data over all sessions.
     *
     * @return array
     */
    private function collectSessionInfos()
    {
        $sessions = $this->training->getSessions();

        // determine if session has no present trainee
        foreach ($sessions as $session) {
            $presentRegistrations = $this->container->get('doctrine')->getRepository(AbstractInscription::class)->findBy(array(
                'session' => $session,
                'presencestatus' => $this->container->get('doctrine')->getRepository(PresenceStatus::class)->findBy(array(
                    'machinename' => 'present',
                )),
            ));
            $session->emptyInscriptions = count($presentRegistrations) === 0;
        }

        $infos = array(
            'publics' => array(),
            'dates' => '',
            'trainers' => array(),
            'duration' => 0,
        );

        $publicType = $this->container->get('doctrine')->getRepository(PublicType::class)->findAll();

        if (!empty($sessions)) {
            $sDates = array();
            $sPlaces = array();
            /** @var Session $session */
            foreach ($sessions as $session) {
                // exclude from report if  session has no present trainee
                if (empty($session->getInscriptions())) {
                    continue;
                }
                foreach ($session->getParticipations() as $participation) {
                    $infos['trainers'][$participation->getTrainer()->getId()] = $participation->getTrainer();
                }

                //registrations stats
                if (empty($infos['regstats'])) {
                    $infos['regstats'] = array();
                    $infos['regstats']['allregs'] = array('label' => "Nombre total de demandes d'inscription :", 'count' => 0);
                    $infos['regstats']['present'] = array('label' => 'Nombre total de formés :', 'count' => 0);
                    foreach ($publicType as $pt) {
                        $infos['regstats'][$pt->getId()] = array('label' => $pt->getName(), 'count' => 0);
                    }
                }

                if ($session->getRegistration() !== AbstractSession::REGISTRATION_DEACTIVATED) {
                    /** @var AbstractInscription $inscription */
                    foreach ($session->getInscriptions() as $inscription) {
                        $presStatus = $inscription->getPresenceStatus();
                        if (!empty($presStatus) && ($presStatus->getStatus() == PresenceStatus::STATUS_PRESENT)) {

                            /** @var AbstractTrainee $trainee */
                            $trainee = $inscription->getTrainee();
                            //echo $trainee->getFullName();
                            /** @var PublicType $pt */
                            foreach ($publicType as $pt) {
                                //echo $pt->getId()."-";
                                if (!empty($tpt) && ($tpt->getId() == $pt->getId())) {
                                    ++$infos['regstats'][$pt->getId()]['count'];
                                }
                            }
                            ++$infos['regstats']['present']['count'];
                        }
                    }
                    $infos['regstats']['allregs']['count'] += count($session->getInscriptions());
                } else {
                    $total = 0;
                    /** @var ParticipantsSummary $summary */
                    foreach ($session->getParticipantsSummaries() as $summary) {
                        $infos['regstats'][$summary->getPublicType()->getId()]['count'] += $summary->getCount();
                        $total += $summary->getCount();
                    }
                    $infos['regstats']['allregs']['count'] += $total;
                }

                //costs
                if (empty($infos['costs'])) {
                    $infos['costs'] = array();
                }

                //-- reprography
/*
                if (empty($infos['costs']['reprography'])) {
                    $infos['costs']['reprography'] = array('label' => 'Fais de reprographie', 'amount' => $session->getReprographyCost());
                } else {
                    $infos['costs']['reprography']['amount'] += $session->getReprographyCost();
		}
*/

                //-- others
/*
                if (empty($infos['costs']['other'])) {
                    $infos['costs']['other'] = array('label' => 'Divers', 'amount' => $session->getOtherCost());
                } else {
                    $infos['costs']['other']['amount'] += $session->getOtherCost();
                }
 */

                //takings
                if (empty($infos['takings'])) {
                    $infos['takings'] = array();
                }
                //-- subscription rights
/*
                if (empty($infos['takings']['subscriptionrights'])) {
                    $infos['takings']['subscriptionrights'] = array('label' => "Droits d'inscription", 'amount' => $session->getSubscriptionRightTaking());
                } else {
                    $infos['takings']['subscriptionrights']['amount'] += $session->getSubscriptionRightTaking();
                }
*/
		
                //-- others
/*
		if (empty($infos['takings']['other'])) {
                    $infos['takings']['other'] = array('label' => 'Autres', 'amount' => $session->getOtherTaking());
                } else {
                    $infos['takings']['other']['amount'] += $session->getOtherTaking();
		}
*/
                $sDates[] = $this->getDates($session);
                $sPlaces[] = $session->getPlace();
                $infos['duration'] += $session->getHourNumber();
            }
        }

        //material names
        $mNames = array();
        /** @var AbstractMaterial $material */
/*
        foreach ($this->training->getMaterials() as $material) {
            $mNames [] = $material->getName();
	}
*/
        $infos['materials'] = implode(', ', $mNames);

        //dates
        $infos['dates'] = implode(', ', $sDates);
        $infos['places'] = implode(', ', $sPlaces);

        return $infos;
    }

    /**
     * Builds the balance sheet.
     *
     * @throws \PHPExcel_Exception
     */
    protected function buildBalanceSheet()
    {
        $infos = $this->collectSessionInfos();

        $currentRow = 1;
        $currentColumn = 'A';
        /* @var  phpExcelObject */
        $this->phpExcelObject = $this->spreadsheet->getProperties();

        $linebreaks = array();

        $this->spreadsheet->getProperties()->setCreator('Sygefor')
            ->setTitle('Bilan')
            ->setSubject($this->training->getName());

        //global default cell styles
        $styleArray = array(
            'font' => array(
                'name' => 'Arial',
                'size' => 9,
            ),
            'borders' => array(
                'inside' => array(
                    'style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array(
                        'rgb' => '000000',
                    ),
                ),
                'outline' => array(
                    'style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array(
                        'rgb' => '000000',
                    ),
                ),
            ),
        );

        //style for thicker line separating sections
        $linechangeStyleArray = array(
            'borders' => array(
                'bottom' => array(
                    'style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                ),
            ),

        );

        //storing line for later adding double thickness
        $linebreaks [] = $currentRow;

        /** @var \PHPExcel_Worksheet $activeSheet */
        $activeSheet = $this->spreadsheet->setActiveSheetIndex(0);

        $activeSheet->getDefaultRowDimension()->setRowHeight(15);

        $activeSheet->setCellValue('A'.$currentRow, 'Fiche Action')
            ->setCellValue('B'.$currentRow, $this->training->getTypeLabel())
            ->setCellValue('C'.$currentRow, 'n°'.$this->training->getNumber());

        //some style for heading row
        $activeSheet->getStyle('A'.$currentRow.':C'.$currentRow)->getFont()->setBold(true)->setSize(12);
        $activeSheet->getStyle('A'.$currentRow.':C'.$currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $activeSheet->getStyle('A'.$currentRow.':C'.$currentRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
        $activeSheet->getRowDimension($currentRow)->setRowHeight(30);

        //------------------------------------------------------------------------------------
        ++$currentRow;
        //name
        $activeSheet->setCellValue('A'.$currentRow, "Intitulé de l'action :")
            ->setCellValue('B'.$currentRow, $this->training->getName());
        $activeSheet->mergeCells('B'.$currentRow.':C'.$currentRow);

        ++$currentRow;
        //theme
        $activeSheet->setCellValue('A'.$currentRow, 'Thème :')
            ->setCellValue('B'.$currentRow, trim($this->training->getTheme()->getName()));

        //storing line for later adding double thickness
        $linebreaks [] = $currentRow;

        ++$currentRow;
        //theme
/*
        $activeSheet->setCellValue('A'.$currentRow, 'Code de la formation :')
            ->setCellValue('B'.$currentRow, trim($this->training->getTrainingCode()));
 */
        //storing line for later adding double thickness
        $linebreaks [] = $currentRow;

        //------------------------------------------------------------------------------------
        if (method_exists($this->training, 'getPrerequisites')) {
            ++$currentRow;
            //prerequis
            $activeSheet->setCellValue('A'.$currentRow, 'Prérequis :')
                ->setCellValue('B'.$currentRow, $this->training->getPrerequisites());
            $activeSheet->mergeCells('B'.$currentRow.':C'.$currentRow);
            $linebreaks [] = $currentRow;
        }
        //------------------------------------------------------------------------------------
        ++$currentRow;
        //program
        $activeSheet->setCellValue('A'.$currentRow, 'Programme :')
            ->setCellValue('B'.$currentRow, $this->training->getProgram());
        $activeSheet->getStyle('B'.$currentRow)->getAlignment()->setWrapText(true);
        $activeSheet->getRowDimension($currentRow)->setRowHeight(60);
        $activeSheet->mergeCells('B'.$currentRow.':C'.$currentRow);
        //storing line for later adding double thickness
        $linebreaks [] = $currentRow;

        //------------------------------------------------------------------------------------
        ++$currentRow;
        //objectives
        $activeSheet->setCellValue('A'.$currentRow, 'Objectifs :')
            ->setCellValue('B'.$currentRow, $this->training->getDescription());
        $activeSheet->getStyle('B'.$currentRow)->getAlignment()->setWrapText(true);
        $activeSheet->getRowDimension($currentRow)->setRowHeight(60);
        $activeSheet->mergeCells('B'.$currentRow.':C'.$currentRow);
        //storing line for later adding double thickness
        $linebreaks [] = $currentRow;

        //duration
        if (!empty($infos['duration'])) {
            ++$currentRow;
            $activeSheet->setCellValue('A'.$currentRow, 'Durée :')
                ->setCellValue('B'.$currentRow, $infos['duration'].' heure(s)');
        }

        //------------------------------------------------------------------------------------
        ++$currentRow;
        //dates
        $activeSheet->setCellValue('A'.$currentRow, 'Date(s) :')
            ->setCellValue('B'.$currentRow, (!empty($infos['dates'])) ? $infos['dates'] : '');
        $activeSheet->mergeCells('B'.$currentRow.':C'.$currentRow);

        //------------------------------------------------------------------------------------
        ++$currentRow;
        //places
        $activeSheet->setCellValue('A'.$currentRow, 'Lieux :')
            ->setCellValue('B'.$currentRow, (!empty($infos['places'])) ? $infos['places'] : '');
        $activeSheet->mergeCells('B'.$currentRow.':C'.$currentRow);

        //storing line for later adding double thickness
        $linebreaks [] = $currentRow;

        //------------------------------------------------------------------------------------
        ++$currentRow;
        $mergeFrom = $currentRow;
        //public
        $activeSheet->setCellValue('A'.$currentRow, 'Publics :');
        $nT = 0;
        if (!empty($infos['regstats'])) {
            foreach ($infos['regstats'] as $stat) {
                $activeSheet->setCellValue('B'.($currentRow + $nT), $stat['label']);
                $activeSheet->setCellValue('C'.($currentRow + $nT), $stat['count']);
                ++$nT;
            }
            $currentRow += $nT - 1;
        }
        $mergeTo = $currentRow;
        $activeSheet->mergeCells('A'.$mergeFrom.':A'.$mergeTo);

        //storing line for later adding double thickness
        $linebreaks [] = $currentRow;

//        $currentRow++;
//        //program
//        $activeSheet->setCellValue('A'.$currentRow, "Programme :")
//            ->setCellValue('B'.$currentRow, $this->training->getDisciplinary->getName());

        //------------------------------------------------------------------------------------
        //trainers
        if (!empty($infos['trainers'])) {
            ++$currentRow;

            $mergeFrom = $currentRow;
            $activeSheet->setCellValue('A'.$currentRow, 'Intervenants :');
            $nT = 0;
            /** @var AbstractTrainer $trainer */
            foreach ($infos['trainers'] as $trainer) {
                $activeSheet->setCellValue('B'.($currentRow + $nT), $trainer->getFullName());
                $activeSheet->setCellValue('C'.($currentRow + $nT), $trainer->getOrganization()->getName());
                ++$nT;
            }
            $currentRow += $nT - 1;
            $mergeTo = $currentRow;
            //storing line for later adding double thickness
            $linebreaks [] = $currentRow;
            $activeSheet->mergeCells('A'.$mergeFrom.':A'.$mergeTo);

            //storing line for later adding double thickness
            $linebreaks [] = $currentRow;
        }

        //------------------------------------------------------------------------------------
        ++$currentRow;
        //responsable

        $supervisor = '';
        if ($this->training->getSupervisor()) {
            $supervisor = $this->training->getSupervisor()->getFullName();
        }

        $activeSheet->setCellValue('A'.$currentRow, 'Responsable '.(($this->training->getType() != 'meeting') ? 'pédagogique ' : ' ').':')
            ->setCellValue('B'.$currentRow, $supervisor);
        $activeSheet->mergeCells('B'.$currentRow.':C'.$currentRow);
        //storing line for later adding double thickness
        $linebreaks [] = $currentRow;
        //------------------------------------------------------------------------------------
        ++$currentRow;
        //costs
        $activeSheet->setCellValue('A'.$currentRow, 'Coûts :');
        $mergeFrom = $currentRow;

        $nT = 0;
        if (!empty($infos['costs'])) {
            foreach ($infos['costs'] as $cost) {
                $activeSheet->setCellValue('B'.($currentRow + $nT), $cost['label']);
                $activeSheet->setCellValue('C'.($currentRow + $nT), $cost['amount']);
                ++$nT;
            }
            $currentRow += $nT - 1;
        }
        //storing line for later adding double thickness
        $linebreaks [] = $currentRow;

        $mergeTo = $currentRow;
        $activeSheet->mergeCells('A'.$mergeFrom.':A'.$mergeTo);

        //------------------------------------------------------------------------------------
        ++$currentRow;
        //takings
        $activeSheet->setCellValue('A'.$currentRow, 'Recettes :');
        $mergeFrom = $currentRow;

        if (!empty($infos['takings'])) {
            $nT = 0;
            foreach ($infos['takings'] as $cost) {
                $activeSheet->setCellValue('B'.($currentRow + $nT), $cost['label']);
                $activeSheet->setCellValue('C'.($currentRow + $nT), $cost['amount']);
                ++$nT;
            }
        }
        $currentRow += $nT - 1;
        //storing line for later adding double thickness
        $linebreaks [] = $currentRow;

        $mergeTo = $currentRow;
        $activeSheet->mergeCells('A'.$mergeFrom.':A'.$mergeTo);

        //------------------------------------------------------------------------------------
        ++$currentRow;
        //materials
        $activeSheet->setCellValue('A'.$currentRow, 'Documents et supports :')
            ->setCellValue('B'.$currentRow, $infos['materials']);
        $activeSheet->mergeCells('B'.$currentRow.':C'.$currentRow);

        //------------------------------------------------------------------------------------
        ++$currentRow;
        //comments
        $activeSheet->setCellValue('A'.$currentRow, 'Commentaires :')
            ->setCellValue('B'.$currentRow, $this->training->getComments());
        $activeSheet->mergeCells('B'.$currentRow.':C'.$currentRow);

        $this->spreadsheet->getActiveSheet()->setTitle('Bilan');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $this->spreadsheet->setActiveSheetIndex(0);

        //setting columnwidth
        $activeSheet->getColumnDimension('A')->setWidth('25');
        $activeSheet->getColumnDimension('B')->setWidth('42');
        $activeSheet->getColumnDimension('C')->setWidth('18');

        //left cells are aligned vertically on top
        $activeSheet->getStyle('A2:A'.$currentRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

        //coloring cells border in black
        $activeSheet->getStyle('A2:C'.$currentRow)->applyFromArray($styleArray);

        // adding thicker borders on line breaks
        foreach ($linebreaks as $lb) {
            $activeSheet->getStyle('A'.$lb.':C'.$lb)->applyFromArray($linechangeStyleArray);
        }
    }

    /**
     * @param AbstractSession $session
     *
     * @return string
     */
    protected function getDates($session)
    {
        if (!$session->getDateEnd() || $session->getDateBegin()->format('d/m/y') === $session->getDateEnd()->format('d/m/y')) {
            return $session->getDateBegin()->format('d/m/Y');
        }

        return $session->getDateBegin()->format('d/m/Y').' - '.$session->getDateEnd()->format('d/m/Y');
    }
}
