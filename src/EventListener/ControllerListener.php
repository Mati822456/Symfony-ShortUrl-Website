<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;

 class ControllerListener {
     /**
      * @var \Twig\Environment
      */
     private $twig;

     private $em;

     private $security;

     public function __construct( Environment $twig, EntityManagerInterface $em, Security $security) {
         $this->twig = $twig;
         $this->em = $em;
         $this->security = $security;
     }
     public function onKernelController(): void {
        if($this->security->getUser()){
            $query = $this->em
            ->createQuery("SELECT COUNT(n.id) FROM App\Entity\Notification n WHERE n.user = :user AND n.opened = 0")
            ->setParameter("user", $this->security->getUser()->getId());

        $result = implode("", $query->getResult()[0]);

        $this->twig->addGlobal('to_read', $result);
        }
     }
 }

?>