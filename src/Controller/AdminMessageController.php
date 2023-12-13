<?php

namespace App\Controller;

use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminMessageController extends AbstractController
{
    public function __construct(private ContactRepository $contactRepository, private EntityManagerInterface $em){}

    #[Route('/admin/messages', name: 'admin_messages', methods: ['GET'])]
    public function index(Request $request): Response
    {   
        $query = $request->get('q');

        if ($query) {
            $messages = $this->contactRepository->findLike($query);
        } else {
            $messages = $this->contactRepository->findBy([], ['id' => 'DESC']);
        }

        return $this->render('admin/messages.html.twig', [
            'messages' => $messages,
            'search' => $query
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
