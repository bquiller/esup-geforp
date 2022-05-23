<?php

namespace App\Repository;

use App\Entity\Core\Term\Theme;
use App\Entity\Internship;
use App\Entity\Organization;
use App\Entity\Session;
use App\Entity\Trainer;
use App\Entity\Participation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TrainingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Internship::class);
    }

    public function getTrainingsList($keyword, $filters)
    {
        $qb = $this->createQueryBuilder('internship');
        $qb
            ->select('internship')
            /* Keyword (recherche par mot clé) */
            ->innerJoin(Organization::class, 'o')
            ->innerJoin(Theme::class, 'th')
            ->innerJoin(Trainer::class, 'trainer')
            ->innerJoin(Participation::class, 'p')
            ->innerJoin(Session::class, 's')

            // FILTRE KEYWORD
            ->where('internship.name LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');


        // FILTRE CENTRE
        if (isset($filters['training.organization.name.source'])) {
            $qb
                ->andWhere('internship.organization = o.id')
                ->andWhere('o.name in (:centers)')
                ->setParameter('centers', $filters['training.organization.name.source']);
        }

        // FILTRE ANNEE
        if (isset($filters['year'])) {
            $qb
                ->andWhere('s.training = internship.id')
                ->andWhere('YEAR(s.datebegin) in (:years)')
                ->setParameter('years', $filters['year']);
        }

        // FILTRE SEMESTRE
        if( isset($filters['semester']) ) {
            if ($filters['semester']  == 1) {
                $monthFrom = 1; $monthTo = 6;
            } else {
                $monthFrom = 7; $monthTo = 12;
            }
            $qb
                ->andWhere('s.training = internship.id')
                ->andWhere('MONTH(s.datebegin) BETWEEN :monthFrom and :monthTo')
                ->setParameter('monthFrom', $monthFrom)
                ->setParameter('monthTo', $monthTo);
        }

        //FILTRE THEME
        if( isset($filters['theme.name'])) {
            $qb
                ->andWhere('internship.theme = th.id')
                ->andWhere('th.name in (:themes)')
                ->setParameter('themes', $filters['theme.name']);
        }

        // FILTRE FORMATEUR
        if( isset($filters['trainers.fullName']) ) {
            $fullName = explode(" ", $filters['trainers.fullName']);
            $lastName = array_pop($fullName);
            $firstName = array_shift($fullName);
            $qb
                ->andWhere('s.training = internship.id')
                ->andWhere('s.id = p.session')
                ->andWhere('trainer.id = p.trainer')
                ->andWhere('trainer.lastname = :trainerLastName AND trainer.firstname = :trainerFirstName')
                ->setParameter('trainerLastName', $lastName)
                ->setParameter('trainerFirstName', $firstName);
        }

        //FILTRE PROMOTION (true,false) = (0,1)
        if (isset($filters['nextSession.promote'])) {
            $qb
                ->andWhere('s.training = internship.id')
                ->andWhere('s.promote = :promote')
                ->setParameter('promote', $filters['nextSession.promote']);
        }

        //FILTRE CODE numero du stage
        if (isset($filters['training.number'])) {
            $qb
                ->andWhere('internship.number = :number')
                ->setParameter('number', $filters['training.number']);
        }

        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result;
    }

    public function getNbTrainings($query_filters, $keyword, $aggs, $name)
    {
        $qb = $this->createQueryBuilder('internship');
        $qb
            ->select('internship')
            /* Keyword (recherche par mot clé) */
            ->innerJoin(Organization::class, 'o')
            ->innerJoin(Theme::class, 'th')
            ->innerJoin(Trainer::class, 'trainer')
            ->innerJoin(Participation::class, 'p')
            ->innerJoin(Session::class, 's')

            // FILTRE KEYWORD
            ->where('s.name LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');


        // FILTRE CENTRE
        if(isset( $aggs['training.organization.name.source'])) {
            $qb
                ->andWhere('internship.organization = o.id')
                ->andWhere('o.name = :center')
                ->setParameter('center', $name);
        } elseif (isset($query_filters['training.organization.name.source'])) {
            $qb
                ->andWhere('internship.organization = o.id')
                ->andWhere('o.name in (:centers)')
                ->setParameter('centers', $query_filters['training.organization.name.source']);
        }

        // FILTRE ANNEE
        if (isset($aggs['year'])) {
            $qb
                ->andWhere('s.training = internship.id')
                ->andWhere('YEAR(s.datebegin) = :year')
                ->setParameter('year', $name);
        } elseif (isset($query_filters['year'])) {
            $qb
                ->andWhere('s.training = internship.id')
                ->andWhere('YEAR(s.datebegin) in (:years)')
                ->setParameter('years', $query_filters['year']);
        }

        // FILTRE SEMESTRE
        if (isset($aggs['semester'])) {
            if ($name  == 1) {
                $monthFrom = 1; $monthTo = 6;
            } else {
                $monthFrom = 7; $monthTo = 12;
            }
            $qb
                ->andWhere('s.training = internship.id')
                ->andWhere('MONTH(s.datebegin) BETWEEN :monthFrom and :monthTo')
                ->setParameter('monthFrom', $monthFrom)
                ->setParameter('monthTo', $monthTo);
        } elseif( isset($query_filters['semester']) ) {
            if ($query_filters['semester']  == 1) {
                $monthFrom = 1; $monthTo = 6;
            } else {
                $monthFrom = 7; $monthTo = 12;
            }
            $qb
                ->andWhere('s.training = internship.id')
                ->andWhere('MONTH(s.datebegin) BETWEEN :monthFrom and :monthTo')
                ->setParameter('monthFrom', $monthFrom)
                ->setParameter('monthTo', $monthTo);
        }

        //FILTRE THEME
        if(isset( $aggs['theme.name'])) {
            $qb
                ->andWhere('internship.theme = th.id')
                ->andWhere('th.name = :theme')
                ->setParameter('theme', $name);
        } elseif (isset($query_filters['theme.name'])) {
            $qb
                ->andWhere('internship.theme = th.id')
                ->andWhere('th.name in (:themes)')
                ->setParameter('themes', $query_filters['theme.name']);
        }

        //FILTRE PROMOTION (true,false) = (0,1)
        if(isset( $aggs['nextSession.promote'])) {
            $qb
                ->andWhere('s.training = internship.id')
                ->andWhere('s.promote = :promote')
                ->setParameter('promote', $name);
        } elseif( isset($query_filters['nextSession.promote']) ) {
            $qb
                ->andWhere('s.training = internship.id')
                ->andWhere('s.promote = :promote')
                ->setParameter('promote', $query_filters['nextSession.promote']);
        }

        // FILTRE FORMATEUR
        if(isset( $aggs['trainers.fullName'])) {
            $qb
                ->andWhere('s.training = internship.id')
                ->andWhere('s.id = p.session')
                ->andWhere('trainer.id = p.trainer')
                ->andWhere('trainer.id = :id ')
                ->setParameter('id', $name);
        } elseif( isset($query_filters['trainers.fullName']) ) {
            /* le front envoie un full name (prénom+nom), je le découpe et ne récupère que le nom de famille */
            $fullName = explode(" ", $query_filters['participations.trainer.fullName']);
            $lastName = array_pop($fullName);
            $firstName = array_shift($fullName);
            $qb
                ->andWhere('s.training = internship.id')
                ->andWhere('s.id = p.session')
                ->andWhere('s.id = p.session')
                ->andWhere('trainer.id = p.trainer')
                ->andWhere('trainer.lastname = :trainerLastName AND trainer.firstname = :trainerFirstName')
                ->setParameter('trainerLastName', $lastName)
                ->setParameter('trainerFirstName', $firstName);
        }

        // On compte le nb de sessions en résultat
        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($qb->getQuery());
        $totalRows = count($paginator);

        return $totalRows;
    }

}
