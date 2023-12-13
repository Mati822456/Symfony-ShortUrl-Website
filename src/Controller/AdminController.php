<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{

    public function __construct(private EntityManagerInterface $em){}

    // Retrieving an Array or String from database from the given query
    public function getQuery($dql, $param = null, $param_value = null, $str = false)
    {
        if($param){
            $query = $this->em
                      ->createQuery($dql)
                      ->setParameter($param, $param_value);
        }else{
            $query = $this->em
                      ->createQuery($dql);
        }

        if($str){
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

    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        $array = array();
        // Count All Users
        $array['users'] = $this->getQuery('SELECT COUNT(u.id) FROM App\Entity\User u', null, null , true);
        // Count All Websites
        $array['websites'] = $this->getQuery('SELECT COUNT(w.id) FROM App\Entity\Website w', null, null , true);
        // Count All Websites to Accept
        $array['accept'] = $this->getQuery('SELECT COUNT(w.id) FROM App\Entity\Website w WHERE w.status = 0', null, null , true);
        // Get Today's date
        $today = date("d-m-Y");
        // Count All Websites from Today
        $array['today'] = $this->getQuery("SELECT COUNT(w.id) FROM App\Entity\Website w WHERE w.created_date >= ('{$today} 00:00:00') AND w.created_date <= ('{$today} 23:59:59')", null, null , true);
        // Count All Visits
        $array['visits'] = $this->getQuery('SELECT SUM(w.visited) FROM App\Entity\Website w', null, null , true);
        // Count All Visits Today
        $array['tdvisits'] = $this->getQuery("SELECT SUM(w.visited) FROM App\Entity\Website w WHERE w.created_date >= ('{$today} 00:00:00') AND w.created_date <= ('{$today} 23:59:59')", null, null , true);
        // Count All Messages
        $array['messages'] = $this->getQuery("SELECT COUNT(m.id) FROM App\Entity\Contact m", null, null , true);
        // Count All Messages to Read
        $array['toread'] = $this->getQuery("SELECT COUNT(m.id) FROM App\Entity\Contact m WHERE m.visited = 0", null, null , true);

        return $this->render('admin/index.html.twig', [
            'array' => $array,
        ]);
    }

}
