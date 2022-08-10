<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Settings;
use App\Entity\User;
use App\Form\SearchFormType;
use App\Repository\ContactRepository;
use App\Repository\SettingsRepository;
use App\Repository\UserRepository;
use App\Repository\WebsiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use LDAP\Result;
use PhpParser\Node\Expr\Cast\Array_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    private $em;
    private $websiterespository;
    private $settingsRepository;
    private $userrespository;
    private $contactRepository;

    public function __construct(EntityManagerInterface $em, 
                                WebsiteRepository $websiterespository, 
                                SettingsRepository $settingsRepository, 
                                UserRepository $userrespository, 
                                ContactRepository $contactRepository){
        $this->em = $em;
        $this->websiterespository = $websiterespository;
        $this->settingsRepository = $settingsRepository;
        $this->userrespository = $userrespository;
        $this->contactRepository = $contactRepository;
    }

    // Retrieving an Array or String from database from the given query
    public function getquery($dql, $param = null, $param_value = null, $str = false)
    {
        if($param){
            $query = $this->em
                      ->createQuery($dql)
                      ->setParameter($param, $param_value);
        }else{
            $query = $this->em
                      ->createQuery($dql);
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

    // Functions
    // Get All Users
    public function getAllUsers(): Array
    {
        return $this->getquery('SELECT u FROM App\Entity\User u');
    }
    // Get User By Given Email
    public function getUserByName($user)
    {
        return $this->userrespository->findBy(array('email' => $user))[0];
    }
    // Get All Websites
    public function getAllWebsites(): ?Array
    {
        return $this->websiterespository->findBy(array(), array('id' => 'DESC'));
    }
    // Get Website By Given Query
    public function getWebsiteByQuery($query, $param = null, $param_value = null): Array
    {
        return $this->getquery($query, $param, $param_value);
    }
    // Get Website By Given Hash
    public function getWebsiteByHash($hash)
    {
        return $this->websiterespository->findBy(['hash' => $hash])[0];
    }
    // Get All Messages
    public function getAllMessages(): Array
    {
        return $this->contactRepository->findBy([], ['id' => 'DESC']);
    }
    // Get All Messages To Read
    public function getMessagesToRead(): Array
    {
        return $this->contactRepository->findBy(['visited' => 0]);
    }
    // Get Message By Given ID
    public function getMessageById($id)
    {
        return $this->contactRepository->find($id);
    }
    // Get Message By Given Query
    public function getMessageByQuery($query, $param = null, $param_value = null): Array
    {
        return $this->getquery($query, $param, $param_value);
    }
    // Get All Settings
    public function getAllSettings(): Array
    {
        return $this->settingsRepository->findAll();
    }
    // Get Setting By Given Name
    public function getSettingByName($name)
    {
        return $this->settingsRepository->findBy(array('name' => $name))[0];
    }
    // Get Setting By Given Id
    public function getSettingById($id)
    {
        return $this->settingsRepository->find($id);
    }
    // Accept Website By Given Hash
    public function acceptWebsite($id)
    {
        $website = $this->getWebsiteByHash($id);
        if($website){
            $website->setStatus(1);

            $this->sendNotification(1, "Akceptowano stronę", $website->getUser(), $website->getUrl());

            $this->em->flush();
        }
    }
    // Cancel Website by Given Hash with reason
    public function cancelWebsite($hash, $reason)
    {
        $website = $this->websiterespository->findBy(array('hash' => $hash))[0];
        if(in_array('ROLE_ADMIN', $this->getUser()->getRoles(), true) or in_array('ROLE_MOD', $this->getUser()->getRoles(), true)){
            $website->setShorturl($reason);
            $website->setStatus(2);
            $website->setInclude(0);
            
            $this->sendNotification(2, "Odrzucono stronę", $website->getUser(), $website->getUrl().", Powód: ".$reason);

            $this->em->flush();
        }
    }
    // Delete Website by Given Hash
    public function deleteWebsite($hash)
    {
        $website = $this->getWebsiteByHash($hash);
        if($website){
            if(in_array('ROLE_ADMIN', $this->getUser()->getRoles(), true) or in_array('ROLE_MOD', $this->getUser()->getRoles(), true)){
                $this->sendNotification(3, "Usunięto stronę", $website->getUser(), $website->getUrl());
                $this->em->remove($website);
                $this->em->flush();
            }else{
                return $this->render('pagenotfound.html.twig');
            }       
        }
    }
    // Delete Message by Given ID
    public function deleteMessage($id)
    {
        $message = $this->getMessageById($id);
        if($message){
            if(in_array('ROLE_ADMIN', $this->getUser()->getRoles(), true) or in_array('ROLE_MOD', $this->getUser()->getRoles(), true)){
                $this->em->remove($message);
                $this->em->flush();
            }else{
                return $this->render('pagenotfound.html.twig');
            }
        }
    }
    // Change the Status of Given Message
    public function changeMessageStatus($id)
    {
        if($id != ""){
            $message = $this->getMessageById($id);

            if($message){
                if($message->isVisited() == 0){
                    $message->setVisited(1);
                    $this->em->flush();
                }
            }
        }
    }
    // Change the Status of Given Setting
    public function changeSettingStatus($parameter)
    {
        $setting = $this->getSettingById($parameter);
        if($setting){
            if($setting->getStatus() == 0){
                $setting->setStatus(1);
            }else{
                $setting->setStatus(0);
            }
    
            $this->em->flush();
        }
    }
    // Block User
    public function blockUser($name)
    {
        if($name){
            if($this->getUser()){
                if(in_array('ROLE_ADMIN', $this->getUser()->getRoles(), true))
                {
                    $user = $this->getUserByName($name);

                    if(in_array('ROLE_BLOCKED', $user->getRoles(), true)){
                        $user->setRoles(array('ROLE_USER'));
                        $this->sendNotification(4, "Konto odblokowane", $user, "");
                    }else{
                        $user->setRoles(array('ROLE_BLOCKED'));
                        $this->sendNotification(4, "Konto zablokowane", $user, "");
                    }

                    $this->em->flush();
                }
            }
        }
    }
    // Set User Role
    public function setRole($name, $role)
    {
        if($role){
            if($this->getUser()){
                if(in_array('ROLE_ADMIN', $this->getUser()->getRoles(), true))
                {
                    $user = $this->getUserByName($name);
                    $long_role = "";
                    if($role == "Admin"){
                        $user->setRoles(array('ROLE_ADMIN'));
                        $long_role = "Administrator";
                    }elseif($role == "Mod"){
                        $user->setRoles(array('ROLE_MOD'));
                        $long_role = "Moderator";
                    }else{
                        $user->setRoles(array('ROLE_USER'));
                        $long_role = "Użytkownik";
                    }

                    $this->sendNotification(1, "Nadano uprawnienia", $user, $long_role);

                }
            }
        }
    }
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
    // End of Functions

    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        $array = array();
        // Count All Users
        $array['users'] = $this->getquery('SELECT COUNT(u.id) FROM App\Entity\User u', null, null , true);
        // Count All Websites
        $array['websites'] = $this->getquery('SELECT COUNT(w.id) FROM App\Entity\Website w', null, null , true);
        // Count All Websites to Accept
        $array['accept'] = $this->getquery('SELECT COUNT(w.id) FROM App\Entity\Website w WHERE w.status = 0', null, null , true);
        // Get Today's date
        $today = date("d-m-Y");
        // Count All Websites from Today
        $array['today'] = $this->getquery("SELECT COUNT(w.id) FROM App\Entity\Website w WHERE w.created_date >= ('{$today} 00:00:00') AND w.created_date <= ('{$today} 23:59:59')", null, null , true);
        // Count All Visits
        $array['visits'] = $this->getquery('SELECT SUM(w.visited) FROM App\Entity\Website w', null, null , true);
        // Count All Visits Today
        $array['tdvisits'] = $this->getquery("SELECT SUM(w.visited) FROM App\Entity\Website w WHERE w.created_date >= ('{$today} 00:00:00') AND w.created_date <= ('{$today} 23:59:59')", null, null , true);
        // Count All Messages
        $array['messages'] = $this->getquery("SELECT COUNT(m.id) FROM App\Entity\Contact m", null, null , true);
        // Count All Messages to Read
        $array['toread'] = $this->getquery("SELECT COUNT(m.id) FROM App\Entity\Contact m WHERE m.visited = 0", null, null , true);
        // Get Status Of Given Setting
        $setting = $this->getSettingByName('Akceptacja linków');

        return $this->render('admin/index.html.twig', [
            'array' => $array,
            'links' => $setting
        ]);
    }

    #[Route('/users/{mode}/{user}/{role}', name: 'admin_users')]
    public function users($mode = null, $user = null, $role = null): Response
    {
        // BLocking User
        if($mode == "block"){
            $this->blockUser($user);
            return $this->redirectToRoute('admin_users');
        }else if($mode == "setrole"){
            // User role setting
            $this->setRole($user, $role);
            return $this->redirectToRoute('admin_users');
        }elseif($mode == ""){
            // List of All Users
            $users = $this->getAllUsers();
        }else{
            return $this->render('pagenotfound.html.twig');
        }

        return $this->render('admin/index.html.twig',[
            'users' => $users
        ]);
    }


    #[Route('/webadm/{mode}/{param}/{reason}', methods: ['GET'], name: 'admin_websites')]
    public function webadm($mode = null, $param = null, $reason = null, Request $request = null): Response
    {
        // Get Status Of Given Setting
        $setting = $this->getSettingByName('Akceptacja linków');
        $query = null;
        if($mode == "accept"){
            // List of All Websites to Accept
            $websites = $this->websiterespository->findBy(array('status' => 0), array('id' => 'DESC'));
        }elseif($mode == "status"){
            // Accept Website
            $this->acceptWebsite($param);
            return $this->redirectToRoute('admin_websites');
        }elseif($mode == "today"){
            // List of All Websites from today
            $today = date("d-m-Y");
            $websites = $this->getWebsiteByQuery("SELECT w FROM App\Entity\Website w WHERE w.created_date >= ('{$today} 00:00:00') AND w.created_date <= ('{$today} 23:59:59') ORDER BY w.id DESC");
        }elseif($mode == "search"){
            // Search Website By Given String
            $query = $request->query->get('query');
            $websites = $this->getWebsiteByQuery("SELECT w FROM App\Entity\Website w WHERE w.url LIKE :param or w.shorturl LIKE :param", "param", "%".$query."%");
        }elseif($mode == "cancel"){
            // Cancel Website
            $this->cancelWebsite($param, $reason);
            return $this->redirectToRoute('admin_websites');
        }elseif($mode == "delete"){
            // Delete Website by Given Hash
            $this->deleteWebsite($param);
            return $this->redirectToRoute('admin_websites');
        }elseif($mode == ""){
            // List of All Websites
            $websites = $this->getAllWebsites();
        }else{
            return $this->render('pagenotfound.html.twig');
        }

        return $this->render('admin/index.html.twig', [
            'websites' => $websites,
            'links' => $setting,
            'search' => $query
        ]);
    }
    #[Route('/messages/{mode}/{param}', name: 'admin_messages')]
    public function messages($mode = null, $param = null, Request $request = null): Response
    {
        if($mode == "read"){
            // Get All Messages to Read
            $messages = $this->getMessagesToRead();
        }elseif($mode == "search"){
            // Search Message By Given String
            $query = $request->query->get('query');
            $messages = $this->getMessageByQuery("SELECT m FROM App\Entity\Contact m WHERE m.name LIKE :param OR m.subject LIKE :param OR m.message LIKE :param", "param", "%".$query."%");
        }elseif($mode == "status"){
            // Change the Status of Given Message
            $this->changeMessageStatus($param);
            return $this->redirectToRoute('admin_messages');
        }elseif($mode == "delete"){
            // Delete Message by Given id
            $this->deleteMessage($param);
            return $this->redirectToRoute('admin_messages');
        }elseif($mode == ""){
            // Get All Messages
            $messages = $this->getAllMessages();
        }else{
            return $this->render('pagenotfound.html.twig');
        }
        
        return $this->render('admin/index.html.twig', [
            'messages' => $messages,
            'search' => $param
        ]);
    }

    #[Route('/settings/{mode}/{id}', name: 'admin_settings')]
    public function settings($mode = null, $id = null): Response
    {
        if($mode == "status"){
            // Change Status of Given Setting
            $this->changeSettingStatus($id);
            return $this->redirectToRoute('admin_settings');
        }elseif($mode == ""){
            // Get All Settings
            $settings = $this->getAllSettings();
        }else{
            return $this->render('pagenotfound.html.twig');
        }
        
        return $this->render('admin/index.html.twig',[
            'settings' => $settings
        ]);
    }

}
