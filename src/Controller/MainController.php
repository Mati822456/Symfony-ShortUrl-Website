<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\User;
use App\Form\ContactFormType;
use App\Form\EditProfileFormType;
use App\Repository\UserRepository;
use App\Repository\WebsiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Environment;

class MainController extends AbstractController 
{

    public function __construct(private WebsiteRepository $websiteRepository, private UserRepository $userRepository, private EntityManagerInterface $em, private Environment $twig){}

    // Generating random String
    public function generateRandom(): String
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
  
        for ($i = 0; $i < 6; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }

        return $randomString;
    }

    #[Route('/', name: 'app_main', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Pulling out the 5 most visited websites
        $websites = $this->websiteRepository->findMostVisitedWebsites();

        return $this->render('index.html.twig',[
            'websites' => $websites,
            'ip' => $request->getClientIp()
        ]);
    }

    #[Route('/contact', name: 'contact', methods: ['GET', 'POST'])]
    public function contact(Request $request): Response
    {
        // Contact Form
        $message = new Contact();

        $form = $this->createForm(ContactFormType::class, $message);
        if($this->getUser()){
            $form->get('email')->setData($this->getUser()->getEmail());
        }
        
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $newmessage = $form->getData();
            $newmessage->setVisited(0);
            $newmessage->setCreatedDate(date("d-m-Y H:i:s"));

            $this->em->persist($newmessage);
            $this->em->flush();
            return $this->redirectToRoute('contact');
        }
        
        return $this->render('contact.html.twig', [
            'form' => $form->createView()
        ]);
    }
    
    #[Route('/account', name: 'account', methods: ['GET', 'POST'])]
    public function account(UserInterface $user, Request $request, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        
        $newUser = new User();

        $user = $this->userRepository->find($this->getUser());

        $form = $this->createForm(EditProfileFormType::class, $newUser);

        $form->handleRequest($request);

        // User Password Change
        if($form->isSubmitted() && $form->isValid()){
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('password')->getData()
                    )
                );
                
                $this->em->persist($user);
                $this->em->flush();
        }

        return $this->render('panel/account.html.twig', [
            'user' => $user,
            'form' => $form->createView()
        ]);
    }

    #[Route('/url/{hash}', name: 'route_to_link', methods: ['GET'])]
    public function route_to_link($hash): Response
    {
        // Redirect To Shortened Link
        if($hash != null){
            $website = $this->websiteRepository->findBy(['shorturl' => $hash]);
        
            if($website){
                if($website[0]->getStatus() == 1){
                    $website[0]->setVisited($website[0]->getVisited()+1);

                    $this->em->flush();
            
                    return $this->redirect($website[0]->getUrl());
                }
            }
        }

        return $this->render('pagenotfound.html.twig');
    }

}
