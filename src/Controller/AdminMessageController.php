<?php

namespace App\Controller;

use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminMessageController extends AbstractController
{
    public function __construct(private ContactRepository $contactRepository, private EntityManagerInterface $em){}

    #[Route('/admin/messages', name: 'admin_messages', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {   
        $q = $request->get('q');
        $page = $request->query->getInt('page', 1);
        $pageSize = 10;

        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder->select('m')
            ->from('App\Entity\Contact', 'm')
            ->orderBy('m.id', 'ASC');

        if ($request->get('q')) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->like('m.message', ':searchTerm'),
                    $queryBuilder->expr()->like('m.email', ':searchTerm'),
                    $queryBuilder->expr()->like('m.subject', ':searchTerm'),
                    $queryBuilder->expr()->like('m.message', ':searchTerm')
                )
            )->setParameter('searchTerm', '%' . $request->get('q') . '%');
        }

        $queryBuilder->orderBy('m.id', 'DESC');

        $query = $queryBuilder->getQuery();

        $messages = $paginator->paginate(
            $query,
            $page,
            $pageSize
        );

        return $this->render('admin/messages.html.twig', [
            'messages' => $messages,
            'page' => $page,
            'search' => $request->get('q')
        ]);
    }

    #[Route('/admin/messages/{id}', name: 'admin_update_messages', methods: ['PATCH'])]
    public function update(Request $request, $id): Response
    {
        $message = $this->contactRepository->find($id);

        if($message){
            if($message->isVisited() == 0){
                $message->setVisited(1);
                $this->em->flush();
            }
        }

        return $this->redirectToRoute('admin_messages');
    }

    #[Route('/admin/messages/{id}', name: 'admin_destroy_messages', methods: ['DELETE'])]
    public function destroy($id): Response
    {
        $message = $this->contactRepository->find($id);

        if ($message) {
            $this->em->remove($message);
            $this->em->flush();
        }
        
        return $this->redirectToRoute('admin_messages');
    }
}
