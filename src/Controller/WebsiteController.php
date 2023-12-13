<?php

namespace App\Controller;

use App\Entity\Website;
use App\Form\ShortUrlFormType;
use App\Repository\WebsiteRepository;
use App\Repository\SettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class WebsiteController extends AbstractController
{  

    public function __construct(private WebsiteRepository $websiteRepository, private SettingsRepository $settingsRepository, private EntityManagerInterface $em){}
    public function getAllWebsites()
    {
        $websites = $this->getUser()->getWebsite();
        $websites = $websites->toArray();
        return array_reverse($websites);
    }

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

    #[Route('/websites', name: 'index_websites', methods: ['GET'])]
    public function index(Request $request): Response
    {
        if ($request->get('q')) {
            $websites = $this->websiteRepository->findLikeName($request->get('q'), $this->getUser());
        } else {
            $websites = $this->getAllWebsites();
        }
        return $this->render('panel/websites.html.twig', [
            'websites' => $websites,
            'search' => $request->get('q'),
            'ip' => $request->getClientIp()
        ]);
    }

    #[Route('/shorturl', name: 'create_website', methods: ['GET'])]
    public function create(Request $request): Response 
    {
        $shortUrl = new Website();

        $form = $this->createForm(ShortUrlFormType::class, $shortUrl, [
            'action' => $this->generateUrl('store_website'),
        ]);

        $activation = $this->settingsRepository->findBy(array('name' => 'Akceptacja linków'))[0]->getStatus();

        return $this->render('panel/shorturl.html.twig', [
            'form' => $form->createView(),
            'activation' => $activation == 1,
        ]);
    }

    #[Route('/websites', name: 'store_website', methods: ['POST'])]
    public function store(Request $request)
    {
        $activation = $this->settingsRepository->findBy(array('name' => 'Akceptacja linków'))[0]->getStatus();

        $data = $request->get('short_url_form');

        if(!filter_var($data['url'], FILTER_VALIDATE_URL) === false){
            $shortUrl = new Website();
            $shortUrl->setUrl($data['url']);
            $shortUrl->setShorturl($this->generateRandom());
            $shortUrl->setHash($this->generateRandom());
            $shortUrl->setUser($this->getUser());
            $shortUrl->setVisited(0);
            $shortUrl->setInclude(isset($data['include']) && $data['include']);

            if($activation == 0){
                $shortUrl->setStatus(1);
            }else{
                $shortUrl->setStatus(0);
            }

            $shortUrl->setCreatedDate(date("d-m-Y H:i:s"));

            $this->em->persist($shortUrl);
            $this->em->flush();

            return $this->redirectToRoute('index_websites');
        }else{
            $this->addFlash('error', 'Podany ciąg nie jest stroną!');
            return $this->redirectToRoute('create_website');
        }
    }

    #[Route('/websites/{hash}', name: 'update_website', methods: ['PATCH'])]
    public function update(Request $request, $hash): Response
    {
        $website = $this->websiteRepository->findOneBy(['hash' => $hash]);
        if($website){
            if($website->getUser() === $this->getUser()){
                if($website->isInclude() == 1){
                    $website->setInclude(0);
                }else{
                    $website->setInclude(1);
                }
                $this->em->flush();
            }
        }
        return $this->redirectToRoute('index_websites');
    }

    #[Route('/websites/{hash}', name: 'destroy_website', methods: ['DELETE'])]
    public function destroy($hash): Response
    {
        $website = $this->websiteRepository->findOneBy(['hash' => $hash]);
        if($website){
            if($website->getUser() == $this->getUser()){
                $this->em->remove($website);
                $this->em->flush();
            }
        }
        return $this->redirectToRoute('index_websites');
    }
}
