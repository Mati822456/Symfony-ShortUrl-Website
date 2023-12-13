<?php

namespace App\Controller;

use Twig\Environment;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\NotificationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class NotificationController extends AbstractController
{

    public function __construct(private NotificationRepository $notificationRepository, private Environment $twig, private EntityManagerInterface $em){}

    #[Route('/notifications', name: 'index_notification', methods: ['GET'])]
    public function index(): Response
    {
        $notifications = $this->notificationRepository->findBy(['user' => $this->getUser()], ['id' => 'DESC']);
        $twigglobals = $this->twig->getGlobals();
        $to_read = $twigglobals['to_read'];

        if($to_read > 0){
            foreach($notifications as $n){
                if($n->isOpened() == 0){
                    $n->setOpened(1);
                    $this->em->flush();
                }
            }
        }

        $fdate = null;

        if(!empty($notifications)){
            $fdate = explode(" ", $notifications[0]->getCreatedDate());
        }

        return $this->render('panel/notifications.html.twig', [
            'notifications' => $notifications,
            'fdate' => $fdate
        ]);
    }

    #[Route('/notifications/{id}', name: 'destroy_notification', methods: ['DELETE'])]
    public function destroy($id = null): Response
    {
        $notification = $this->notificationRepository->find($id);
        if($notification){
            if($notification->getUser() === $this->getUser()){
                $this->em->remove($notification);
                $this->em->flush();
            }
        }
        return $this->redirectToRoute('index_notification');
    }
}
