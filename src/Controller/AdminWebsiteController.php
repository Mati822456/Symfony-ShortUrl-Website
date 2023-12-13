<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\WebsiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminWebsiteController extends AbstractController
{

    public function __construct(private WebsiteRepository $websiteRepository, private EntityManagerInterface $em){}

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

    #[Route('/admin/websites', name: 'admin_websites', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $query = $request->get('q');
        $filter = $request->get('filter');

        if ($filter){
            if ($filter === 'today'){
                $today = new \DateTimeImmutable();
                $startDate = $today->setTime(0, 0, 0)->format("d-m-Y H:i:s");
                $endDate = $today->setTime(23, 59, 59)->format("d-m-Y H:i:s");

                $qb = $this->em->createQueryBuilder();
                $qb->select('w')
                    ->from('App\Entity\Website', 'w')
                    ->where('w.created_date >= :startOfDay')
                    ->andWhere('w.created_date <= :endOfDay')
                    ->orderBy('w.id', 'DESC')
                    ->setParameter('startOfDay', $startDate)
                    ->setParameter('endOfDay', $endDate);
                $websites = $qb->getQuery()->getResult();
            } else if ($filter === 'accept'){
                $websites = $this->websiteRepository->findBy(['status' => 0], array('id' => 'DESC'));
            }
        } else {
            if ($query) {
                $websites = $this->websiteRepository->findLikeName($query);
            } else {
                $websites = $this->websiteRepository->findBy(array(), array('id' => 'DESC'));
            }
        }

        return $this->render('admin/websites.html.twig', [
            'websites' => $websites,
            'search' => $query
        ]);
    }

    #[Route('/admin/websites/{hash}', name: 'admin_update_website', methods: ['PATCH'])]
    public function update(Request $request, $hash): Response
    {
        $website = $this->websiteRepository->findOneBy(['hash' => $hash]);

        if ($website) {
            $status = $request->get('status');
            $website->setStatus($status);
            if ($status == 1) {
                $this->sendNotification(1, "Akceptowano stronę", $website->getUser(), $website->getUrl());
            } else if ($status == 2) {
                $reason = $request->get('reason');
                $website->setShorturl($reason);
                $this->sendNotification(2, "Odrzucono stronę", $website->getUser(), $website->getUrl().", Powód: ".$reason);
            }
            $this->em->flush();
        }

        return $this->redirectToRoute('admin_websites');
    }

    #[Route('/admin/websites/{hash}', name: 'admin_destroy_website', methods: ['DELETE'])]
    public function destroy($hash): Response
    {
        $website = $this->websiteRepository->findOneBy(['hash' => $hash]);

        if ($website) {
            $this->sendNotification(3, "Usunięto stronę", $website->getUser(), $website->getUrl());
            $this->em->remove($website);
            $this->em->flush();
        }

        return $this->redirectToRoute('admin_websites');
    }
}
