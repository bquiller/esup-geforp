<?php
/*
namespace App\Menu;

use App\Entity\Core\AbstractInscription;
use App\Entity\Core\AbstractOrganization;
use App\Entity\Core\AbstractTrainee;
use App\Entity\Core\AbstractTrainer;
use App\Entity\Core\AbstractTraining;
use App\Entity\Core\Term\AbstractTerm;
use App\Entity\Core\Term\VocabularyInterface;
use App\Entity\Core\User;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\Util\MenuManipulator;
use App\Event\ConfigureMenuEvent;
use Symfony\Component\HttpFoundation\Request;
*/

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\Util\MenuManipulator;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Routing\Router;
use App\Event\ConfigureMenuEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;



class MenuBuilder
{
    private $factory;
    private $authorizationChecker;
    private $router;
    private $registry;

    public function __construct(FactoryInterface $factory, AuthorizationCheckerInterface $authorizationChecker, Router $router)
    {
        $this->factory = $factory;
        $this->authorizationChecker = $authorizationChecker;
        $this->router = $router;
    }

    public function createMainMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');

        // Menu administration et sous menus
        $adminMenu = $menu->addChild('administration', array(
            'label' => 'Administration',
            'icon' => 'gear',
            'uri' => $this->router->generate('core.index'),
        ));
        if ($this->authorizationChecker->isGranted('VIEW', 'App\Entity\Back\Organization')) {
            $adminMenu->addChild('organizations', array(
                    'label' => 'Centres',
                    'uri' => $this->router->generate('organization.index'),
                )
            );
        }

        if ($this->authorizationChecker->isGranted('VIEW', 'App\Entity\Term\AbstractTerm') || $this->authorizationChecker->isGranted('VIEW', 'App\Vocabulary\VocabularyInterface') ) {
            $adminMenu->addChild('taxonomy', array(
                    'label' => 'Vocabulaires',
                    'uri' => $this->router->generate('taxonomy.index'),
                )
            );
        }

        if ($this->authorizationChecker->isGranted('VIEW', 'App\Entity\Core\User')) {
            $adminMenu->addChild('users', array(
                'label' => 'Utilisateurs',
                'uri' => $this->router->generate('user.index'),
            ));
        }



        try {
            if($this->authorizationChecker->isGranted('VIEW', 'App\Entity\Back\Internship')) {
                $item = $menu->addChild('trainings', array(
                    'label' => 'Événements',
                    'icon'  => 'calendar',
                    'uri'   => $this->router->generate('core.index') . '#/training',
                ));

                $item->addChild('internships', array(
                    'label' => 'Stages',
                    'uri'   => $this->router->generate('core.index') . '#/training?type=internship',
                ));

                $item->addChild('sessions', array(
                    'label' => 'Toutes les sessions',
                    'uri'   => $this->router->generate('core.index') . '#/training/session',
                ))->setAttribute('divider_prepend', true);

            }

            if($this->authorizationChecker->isGranted('VIEW', 'App\Entity\Back\Trainee')) {
                $menu->addChild('trainees', array(
                    'label' => 'Publics',
                    'icon'  => 'group',
                    'uri'   => $this->router->generate('core.index') . '#/trainee',
//                    'uri' => $this->router->generate('core.index'),
                ));
            }

            if($this->authorizationChecker->isGranted('VIEW', 'App\Entity\Back\Inscription')) {
                $menu->addChild('inscriptions', array(
                    'label' => 'Inscriptions',
                    'icon'  => 'graduation-cap',
                    'uri'   => $this->router->generate('core.index') . '#/inscription',
                ));
            }

            if($this->authorizationChecker->isGranted('VIEW', 'App\Entity\Back\Institution')) {
                $menu->addChild('institutions', array(
                    'label' => 'Etablissements',
                    'icon'  => 'university',
                    'uri'   => $this->router->generate('core.index') . '#/institution',
                ));
            }

            if($this->authorizationChecker->isGranted('VIEW', 'App\Entity\Back\Trainer')) {
                $menu->addChild('trainers', array(
                    'label' => 'Intervenants',
                    'icon'  => 'user',
                    'uri'   => $this->router->generate('core.index') . '#/trainer',
                ));
            }

        } catch (AuthenticationCredentialsNotFoundException $e) {
        }

        if (!isset($adminMenu)) {
            $menu->removeChild('administration');
        }
        else {
            $manipulator = new MenuManipulator();
            $item = $menu->getChild('administration');
            $manipulator->moveToLastPosition($item);
        }

        return $menu;
    }
}

