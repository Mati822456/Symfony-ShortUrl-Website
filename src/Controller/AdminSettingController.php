<?php

namespace App\Controller;

use App\Repository\SettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminSettingController extends AbstractController
{
    public function __construct(private SettingsRepository $settingsRepository, private EntityManagerInterface $em){}

    #[Route('/admin/settings', name: 'admin_settings', methods: ['GET'])]
    public function index(): Response
    {   
        $settings = $this->settingsRepository->findAll();

        return $this->render('admin/settings.html.twig',[
            'settings' => $settings
        ]);
    }

    #[Route('/admin/settings/{id}', name: 'admin_update_setting', methods: ['PATCH'])]
    public function update(Request $request, $id): Response
    {
        $setting = $this->settingsRepository->find($id);

        if ($setting) {
            if($setting->getStatus() == 0){
                $setting->setStatus(1);
            }else{
                $setting->setStatus(0);
            }
            $this->em->flush();
        }
        
        return $this->redirectToRoute('admin_settings');
    }
}
