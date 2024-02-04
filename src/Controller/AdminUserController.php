<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminUserController extends AbstractController
{

    public function __construct(private UserRepository $userRepository, private EntityManagerInterface $em){}

    // Set Notification With Given Status, Subject, User and Text
    // Notification Statuses Available
    // 1: Success
    // 2: Rejected
    // 3: Deleted
    // 4: Information
    // 5: Warning
    public function sendNotification($status, $subject, $user, $text)
    {
        if($this->getUser()){
            if(in_array('ROLE_ADMIN', $this->getUser()->getRoles(), true) or in_array('ROLE_MOD', $this->getUser()->getRoles(), true)){
                $notify = new Notification();
                $notify->setStatus($status);
                $notify->setSubject($subject);
                $notify->setUser($user);
                $notify->setOpened(0);
                $notify->setCreatedDate(date("d.m.Y H:i"));
                $notify->setText($text);

                $this->em->persist($notify);
                $this->em->flush();
            }
        }
    }

    #[Route('/admin/users', name: 'admin_users', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $page = $request->query->getInt('page', 1);
        $pageSize = 10;

        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder->select('u')
            ->from('App\Entity\User', 'u')
            ->orderBy('u.id', 'ASC');

        $query = $queryBuilder->getQuery();

        $users = $paginator->paginate(
            $query,
            $page,
            $pageSize
        );

        return $this->render('admin/users.html.twig',[
            'users' => $users,
            'page' => $page
        ]);
    }

    #[Route('/admin/users/{id}', name: 'admin_update_user', methods: ['PATCH'])]
    public function update(Request $request, $id): Response
    {
        $user = $this->userRepository->find($id);

        if ($request->get('role') === 'BLOCK') {
            if(in_array('ROLE_BLOCKED', $user->getRoles(), true)){
                $user->setRoles(array('ROLE_USER'));
                $this->sendNotification(4, "Konto odblokowane", $user, "");
            }else{
                $user->setRoles(array('ROLE_BLOCKED'));
                $this->sendNotification(4, "Konto zablokowane", $user, "");
            }
        } else {
            if($user){
                $user->setRoles([$request->get('role')]);
                $this->em->flush();
            }
        }

        return $this->redirectToRoute('admin_users');
    }
}
