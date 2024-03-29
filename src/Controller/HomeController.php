<?php

namespace App\Controller;

use App\Entity\ExcelData;
use App\Form\ExcelDataType;
use App\Repository\ExcelDataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

class HomeController extends AbstractController
{
    /**
     * A description of the entire PHP function.
     *
     * @param Request $request description
     * @param EntityManagerInterface $manager description
     * @param ExcelDataRepository $repository description
     *
     * @throws \Exception description of exception
     */
    #[Route('/', name: 'home')]
    public function index(Request $request, EntityManagerInterface $manager, ExcelDataRepository $repository): Response
    {
        $form = $this->createFormBuilder()
            ->add('file', FileType::class)
            ->add('upload', SubmitType::class, ['label' => 'importer', 'attr' => ['class' => 'btn-sm btn-outline-primary']])
            ->getForm();

        $form->handleRequest($request);
        $dateFilterForm = $this->createFormDateFilter();
        $dateFilterForm->handleRequest($request);
        $errorMessage = null;

        $data = [];

        // Si le filtre est soumis et valide, on récupère les données entre l'intervalle des 2 dates
        if ($dateFilterForm->isSubmitted() && $dateFilterForm->isValid()) {
            $dateEvenement = $dateFilterForm->get('dateEvenement')->getData();
            $dateDernierEvenement = $dateFilterForm->get('dateDernierEvenement')->getData();

            $data = $repository->findByDateRange($dateEvenement, $dateDernierEvenement);
        } else {
            // si le filtre n'est pas soumis, on récupère toutes les données
            $data = $repository->findAll();
        }
        $data = $repository->findAll();

        if ($form->isSubmitted() && $form->isValid()) {
            // On récupère le fichier
            $file = $form->get('file')->getData();
            try {
                // On importe les données
                $this->importExcel($file, $manager);

                return $this->redirectToRoute('home');
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
            }
        }

        return $this->render('home/index.html.twig', [
            'form' => $form->createView(),
            'dateFilterForm' => $dateFilterForm->createView(),
            'data' => $data,
            'errorMessage' => $errorMessage,
        ]);
    }

    private function createFormDateFilter()
    {
        return $this->createFormBuilder()
            ->add('dateEvenement', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date évènement (Veh)',
            ])
            ->add('dateDernierEvenement', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date dernier évènement (Veh)',
            ])
            ->add('filter', SubmitType::class, ['label' => 'Filtrer', 'attr' => ['class' => 'btn-sm btn-outline-primary']])
            ->getForm();
    }

    /**
     * Imports data from an Excel file into the database.
     *
     * @param mixed $file The file to import
     * @param EntityManagerInterface $manager The entity manager
     *
     * @return Some_Return_Value
     *
     * @throws Some_Exception_Class description of exception
     */
    private function importExcel($file, EntityManagerInterface $manager)
    {
        try {
            // On récupère le fichier envoyer
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $row_limit = $sheet->getHighestDataRow();
            $row_range = range(2, $row_limit);
            $valid = 0;
            $insertedDataCount = 0;
            // On récupère les colonnes
            $row_range_start = range(1, 1);
            $row = $sheet->toArray();
            // On récupère les colonnes
            foreach ($row as $i => $t) {
                if ($i == 0) {
                    foreach ($t as $iter => $column_value) {
                        if ($t[$iter] != null) {
                            ++$valid;
                        }
                    }
                }
            }
            // On vérifie les colonnes si elles correspondent aux colonnes attendues depuis l'excel
            if ($valid == 35) {
                // on récupère les colonnes et on les met dans un tableau
                foreach ($row_range_start as $start) {
                    if ($sheet->getCell('A' . $start)->getValue() == 'Compte Affaire'
                        && $sheet->getCell('B' . $start)->getValue() == 'Compte évènement (Veh)'
                        && $sheet->getCell('C' . $start)->getValue() == 'Compte dernier évènement (Veh)'
                        && $sheet->getCell('D' . $start)->getValue() == 'Numéro de fiche'
                        && $sheet->getCell('E' . $start)->getValue() == 'Libellé civilité'
                        && $sheet->getCell('F' . $start)->getValue() == 'Propriétaire actuel du véhicule'
                        && $sheet->getCell('G' . $start)->getValue() == 'Nom'
                        && $sheet->getCell('H' . $start)->getValue() == 'Prénom'
                        && $sheet->getCell('I' . $start)->getValue() == 'N° et Nom de la voie'
                        && $sheet->getCell('J' . $start)->getValue() == 'Complément adresse 1'
                        && $sheet->getCell('K' . $start)->getValue() == 'Code postal'
                        && $sheet->getCell('L' . $start)->getValue() == 'Ville'
                        && $sheet->getCell('M' . $start)->getValue() == 'Téléphone domicile'
                        && $sheet->getCell('N' . $start)->getValue() == 'Téléphone portable'
                        && $sheet->getCell('O' . $start)->getValue() == 'Téléphone job'
                        && $sheet->getCell('P' . $start)->getValue() == 'Email'
                        && $sheet->getCell('Q' . $start)->getValue() == 'Date de mise en circulation'
                        && $sheet->getCell('R' . $start)->getValue() == 'Date achat (date de livraison)'
                        && $sheet->getCell('S' . $start)->getValue() == 'Date dernier évènement (Veh)'
                        && $sheet->getCell('T' . $start)->getValue() == 'Libellé marque (Mrq)'
                        && $sheet->getCell('V' . $start)->getValue() == 'Version'
                        && $sheet->getCell('W' . $start)->getValue() == 'VIN'
                        && $sheet->getCell('X' . $start)->getValue() == 'Immatriculation'
                        && $sheet->getCell('Y' . $start)->getValue() == 'Type de prospect'
                        && $sheet->getCell('Z' . $start)->getValue() == 'Kilométrage'
                        && $sheet->getCell('AA' . $start)->getValue() == 'Libellé énergie (Energ)'
                        && $sheet->getCell('AB' . $start)->getValue() == 'Vendeur VN'
                        && $sheet->getCell('AC' . $start)->getValue() == 'Vendeur VO'
                        && $sheet->getCell('AD' . $start)->getValue() == 'Commentaire de facturation (Veh)'
                        && $sheet->getCell('AE' . $start)->getValue() == 'Type VN VO'
                        && $sheet->getCell('AF' . $start)->getValue() == 'Numéro de dossier VN VO'
                        && $sheet->getCell('AG' . $start)->getValue() == 'Intermediaire de vente VN'
                        && $sheet->getCell('AH' . $start)->getValue() == 'Date évènement (Veh)'
                        && $sheet->getCell('AI' . $start)->getValue() == 'Origine évènement (Veh)'
                    ) {
                        foreach ($row_range as $row) {
                            // On vérifie si le numéro de fiche est vide ou nul, si c'est le cas, on ignore cette ligne
                            $numeroFicheValue = $sheet->getCell('D' . $row)->getValue();
                            if (empty($numeroFicheValue) || $numeroFicheValue === null) {
                                continue;
                            }
                            // On vérifie si le numéro de fiche existe
                            $existingEntity = $manager->getRepository(ExcelData::class)->findOneBy(['numeroFiche' => $sheet->getCell('D' . $row)->getValue()]);

                            // Si le numéro de fiche existe, on ne l'ajoute pas
                            if ($existingEntity) {
                                $existingNumFiche[] = $sheet->getCell('D' . $row)->getValue();
                                continue;
                            }

                            $entity = new ExcelData();
                            // Convertir le numéro de série de date Excel en un objet DateTime
                            $dateMiseCirculationValue = $sheet->getCell('Q' . $row)->getValue();
                            if ($dateMiseCirculationValue !== null) {
                                $dateMiseCirculation = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateMiseCirculationValue);
                            } else {
                                $dateMiseCirculation = null;
                            }
                            // Convertir le numéro de série de date Excel en un objet DateTime
                            $dateAchatValue = $sheet->getCell('R' . $row)->getValue();
                            if ($dateAchatValue !== null) {
                                $dateAchat = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateAchatValue);
                            } else {
                                $dateAchat = null;
                            }
                            // Convertir le numéro de série de date Excel en un objet DateTime
                            $dernierDateValue = $sheet->getCell('S' . $row)->getValue();
                            if ($dernierDateValue !== null) {
                                $dateDernierEvenement = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dernierDateValue);
                            }
                            // Convertir le numéro de série de date Excel en un objet DateTime
                            $dateEvenementValue = $sheet->getCell('AH' . $row)->getValue();
                            if ($dateEvenementValue !== null) {
                                $dateEvenement = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateEvenementValue);
                            }
                            $numeroEtNomVoieValue = $sheet->getCell('I' . $row)->getValue();
                            $numeroEtNomVoie = (string)$numeroEtNomVoieValue;
                            $compteEvenementValue = $sheet->getCell('B' . $row)->getValue();
                            $compteEvenement = (string)$compteEvenementValue;

                            $entity->setCompteAffaire((string)$sheet->getCell('A' . $row)->getValue());
                            $entity->setCompteEvenement($compteEvenement);
                            $entity->setCompteDernierEvenement((string)$sheet->getCell('C' . $row)->getValue());
                            $entity->setNumeroFiche((int)$sheet->getCell('D' . $row)->getValue());
                            $entity->setCivilite((string)$sheet->getCell('E' . $row)->getValue());
                            $entity->setProprietaireVehicule((string)$sheet->getCell('F' . $row)->getValue());
                            $entity->setNom((string)$sheet->getCell('G' . $row)->getValue());
                            $entity->setPrenom((string)$sheet->getCell('H' . $row)->getValue());
                            $entity->setNumeroEtNomVoie($numeroEtNomVoie);
                            $entity->setComplementAdresse1((string)$sheet->getCell('J' . $row)->getValue());
                            $entity->setCodePostal((int)$sheet->getCell('K' . $row)->getValue());
                            $entity->setVille((string)$sheet->getCell('L' . $row)->getValue());
                            $entity->setTelephoneDomicile((int)$sheet->getCell('M' . $row)->getValue());
                            $entity->setTelephonePortable((int)$sheet->getCell('N' . $row)->getValue());
                            $entity->setTelephoneJob((int)$sheet->getCell('O' . $row)->getValue());
                            $entity->setEmailP1((string)$sheet->getCell('P' . $row)->getValue());
                            $entity->setDateMiseCirculation($dateMiseCirculation);
                            $entity->setDateAchat($dateAchat);
                            $entity->setDateDernierEvenement($dateDernierEvenement);
                            $entity->setMarque((string)$sheet->getCell('T' . $row)->getValue());
                            $entity->setVersion((string)$sheet->getCell('V' . $row)->getValue());
                            $entity->setVin((string)$sheet->getCell('W' . $row)->getValue());
                            $entity->setImmatriculation((string)$sheet->getCell('X' . $row)->getValue());
                            $entity->setTypeProspect((string)$sheet->getCell('Y' . $row)->getValue());
                            $entity->setKilometrage((string)$sheet->getCell('Z' . $row)->getValue());
                            $entity->setEnergie((string)$sheet->getCell('AA' . $row)->getValue());
                            $entity->setVendeurVN((string)$sheet->getCell('AB' . $row)->getValue());
                            $entity->setVendeurVo((string)$sheet->getCell('AC' . $row)->getValue());
                            $entity->setCommentaireFacture((string)$sheet->getCell('AD' . $row)->getValue());
                            $entity->setTypeVNVO((string)$sheet->getCell('AE' . $row)->getValue());
                            $entity->setNumeroDossierVNVO((string)$sheet->getCell('AF' . $row)->getValue());
                            $entity->setIntermadiareVenteVN((string)$sheet->getCell('AG' . $row)->getValue());
                            $entity->setDateEvenement($dateEvenement);
                            $entity->setOrigineEvenement((string)$sheet->getCell('AI' . $row)->getValue());
                            // on persiste l'entité dans la base de données
                            $manager->persist($entity);
                            // on incremente le compteur de nouvelles donnée
                            ++$insertedDataCount;
                        }
                    }
                }
            }
            $manager->flush();
            // si la liste des numéros de fiches existant est non vide, on affiche un message d'avertissement
            if (!empty($existingNumFiche)) {
                $this->addFlash('warning', 'Les données suivantes ont été ignorées car les numero de fiches éxiste déjà dans la base de données : ' . implode(', ', $existingNumFiche));
            }
            // on verifie si au moins une nouvelle donnée a été ajoutée à la base de données
            if ($insertedDataCount > 0) {
                $this->addFlash('success', $insertedDataCount . ' entrées ont été ajoutées avec succès à la base de données.');
            } else {
                $this->addFlash('info', 'Aucune nouvelle donnée n\'a été ajoutée à la base de données.');
            }
        } catch (\Exception $e) {
            // Gérer l'exception
            $this->addFlash('danger', 'Une erreur est survenue lors de l\'importation du fichier Excel : ' . $e->getMessage());
        }
    }

    #[Route('/create', name: 'create')]
    public function create(Request $request, EntityManagerInterface $manager)
    {
        $data = new ExcelData();
        $form = $this->createForm(ExcelDataType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $submittedEmail = $form->get('emailP1')->getData();
            $existingData = $manager->getRepository(ExcelData::class)->findOneBy(['emailP1' => $submittedEmail]);
            if ($existingData) {
                $this->addFlash('danger', 'Un enregistrement avec cet email éxiste déjà dans la base de données.');
            } else {
                $manager->persist($data);
                $manager->flush();
                $this->addFlash('success', 'Le client a été bien enregistré.');

                return $this->redirectToRoute('home');
            }
        }

        return $this->render('home/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'edit', requirements: ['id' => Requirement::DIGITS])]
    public function edit(Request $request, int $id, ExcelDataRepository $repository, EntityManagerInterface $manager): Response
    {
        $data = $repository->find($id);
        $form = $this->createForm(ExcelDataType::class, $data);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($data);
            $manager->flush();
            $this->addFlash('success', 'Le client a été bien mis a jours.');

            return $this->redirectToRoute('home');
        }

        return $this->render('home/edit.html.twig', [
            'form' => $form,
            'data' => $data,
        ]);
    }

    #[Route('/show/{id}', name: 'show', requirements: ['id' => Requirement::DIGITS])]
    public function show(Request $request, int $id, ExcelDataRepository $repository): Response
    {
        $data = $repository->find($id);

        return $this->render('home/show.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['DELETE'], requirements: ['id' => Requirement::DIGITS])]
    public function remove(ExcelData $data, EntityManagerInterface $manager)
    {
        $manager->remove($data);
        $manager->flush();
        $this->addFlash('success', 'Le client a été bien supprimer');

        return $this->redirectToRoute('home');
    }
}
