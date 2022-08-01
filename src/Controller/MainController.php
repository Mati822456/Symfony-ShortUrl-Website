<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\Website;
use App\Entity\User;
use App\Form\ContactFormType;
use App\Form\EditProfileFormType;
use App\Form\ShortUrlFormType;
use App\Repository\SettingsRepository;
use App\Repository\UserRepository;
use App\Repository\WebsiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;


class MainController extends AbstractController 
{

    private $em;
    private $websiterespository;
    private $userrespository;
    private $settingsrespository;

    public function __construct(WebsiteRepository $websiteRepository, UserRepository $userrespository,EntityManagerInterface $em, SettingsRepository $settingsrespository)
    {
        $this->em = $em;
        $this->websiterespository = $websiteRepository;
        $this->userrespository = $userrespository;
        $this->settingsrespository = $settingsrespository;
    }

    // Retrieving an Array or String from database from the given query
    public function getquery($dql, $parameter = null, $param_value = null, $str = false, $max = null)
    {
        if($max){
            if($max > 0){
                if($parameter){
                    $query = $this->em
                      ->createQuery($dql)
                      ->setMaxResults($max)
                      ->setParameter($parameter, $param_value);
                }else{
                    $query = $this->em
                      ->createQuery($dql)
                      ->setMaxResults($max);
                }
            } 
        }else{
            if($parameter){
                $query = $this->em
                      ->createQuery($dql)
                      ->setParameter($parameter, $param_value);
            }else{
                $query = $this->em
                      ->createQuery($dql);
            }
        }   
        
        if($str == true){
            $result = implode("", $query->getResult()[0]);
            if($result == null){
                return 0;
            }else{
                return $result;
            }
        }else{
            return $query->getResult();
        }
    }

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
    // Get All Websites
    public function getAllWebsites()
    {
        $websites = $this->getUser()->getWebsite();
        $websites = $websites->toArray();
        return array_reverse($websites);
    }
    // Get Website By Given Hash
    public function getWebsiteByHash($hash)
    {
        return $this->websiterespository->findBy(['hash' => $hash])[0];
    }
    // Pulling out the 3 most visited websites
    public function getMostVisitedWebsites(): Array
    {
        return $this->getquery('SELECT w FROM App\Entity\Website w WHERE w.status = 1 and w.include = 1 ORDER BY w.visited DESC', null , null, false, 3);
    }
    // Get Websites By Search Query
    public function searchQuery($parameter): Array
    {
        return $this->getquery("SELECT w FROM App\Entity\Website w WHERE (w.user = '{$this->getUser()->getId()}') AND (w.url LIKE :param or w.shorturl LIKE :param) ORDER BY w.id DESC", 'param', "%".$parameter."%");
    }
    // Delete Website By Given Hash
    public function deleteWebsite($hash)
    {
        $website = $this->getWebsiteByHash($hash);
        if($website){
            if($website->getUser() == $this->getUser()){
                $this->em->remove($website);
                $this->em->flush();
            }
        }
    }
    // Changing the link status to be included in the ranking
    public function changeRankingStatus($hash)
    {
        $website = $this->getWebsiteByHash($hash);
        if($website){
            if($website->getUser() == $this->getUser()){
                if($website->isInclude() == 1){
                    $website->setInclude(0);
                }else{
                    $website->setInclude(1);
                }
                $this->em->flush();
            }
        }
    }

    #[Route('/', name: 'app_main')]
    public function index(): Response
    {
        // Pulling out the 3 most visited websites
        $websites = $this->getMostVisitedWebsites();
        
        return $this->render('index.html.twig',[
            'websites' => $websites
        ]);
    }
    #[Route('/contact', name: 'contact')]
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
    #[Route('/shorturl', name: 'short')]
    public function short(Request $request): Response
    {
        $shorturl = new Website();

        $form = $this->createForm(ShortUrlFormType::class, $shorturl);

        $form->handleRequest($request);

        // Adding new shorturl
        if($form->isSubmitted() && $form->isValid()){

            if(!filter_var($form->getData()->getUrl(), FILTER_VALIDATE_URL) === false){
                $newshorturl = $form->getData();
                $newshorturl->setShorturl($this->generateRandom());
                $newshorturl->setHash($this->generateRandom());
                $newshorturl->setUser($this->getUser());
                $newshorturl->setVisited(0);

                if($this->settingsrespository->findBy(array('name' => 'Akceptacja linków'))[0]->getStatus() == 0){
                    $newshorturl->setStatus(1);
                }else{
                    $newshorturl->setStatus(0);
                }

                $newshorturl->setCreatedDate(date("d-m-Y H:i:s"));

                $this->em->persist($newshorturl);
                $this->em->flush();

                return $this->redirectToRoute('websites');
            }else{
                return $this->render('panel/shorturl.html.twig', [
                    'error' => 'Podany ciąg nie jest stroną!',
                    'form' => $form->createView()
                ]);
            }
            
        }
        
        return $this->render('panel/shorturl.html.twig', [
            'form' => $form->createView()
        ]);
    }
    #[Route('/websites/{mode}/{id}', methods: ['GET'], name: 'websites')]
    public function websites($mode = null, $id = null, Request $request = null): Response
    {
        $query = null;
        if($mode == "search"){
            // Search Website By Given String
            $query = $request->query->get('query');
            $websites = $this->searchQuery($query);
        }elseif($mode == "delete"){
            // Deleting website
            $this->deleteWebsite($id);
            return $this->redirectToRoute('websites');
        }elseif($mode == "include"){
            // Changing the link status to be included in the ranking
            $this->changeRankingStatus($id);
            return $this->redirectToRoute('websites');
        }elseif($mode == ""){
            // List of All Websites
            $websites = $this->getAllWebsites();
        }else{
            return $this->render('pagenotfound.html.twig');
        }
        
        return $this->render('panel/websites.html.twig', [
            'websites' => $websites,
            'search' => $query,
            'ip' => $request->getClientIp()
        ]);
    }
    #[Route('/account', name: 'account')]
    public function account(UserInterface $user, Request $request, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        
        $newUser = new User();

        $user = $this->userrespository->find($this->getUser());

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
    
    #[Route('/url/{hash}', methods: ['GET'], name: 'route_to_link')]
    public function route_to_link($hash): Response
    {
        // Redirect To Shortened Link
        if($hash != null){
            $website = $this->websiterespository->findBy(['shorturl' => $hash]);
        
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
