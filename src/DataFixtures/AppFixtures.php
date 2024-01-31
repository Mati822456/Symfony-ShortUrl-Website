<?php

namespace App\DataFixtures;

use App\Entity\Settings;
use App\Entity\User;
use App\Entity\Website;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{

    private $hasher;

    public function __construct (UserPasswordHasherInterface $hasher) 
    {
        $this->hasher = $hasher;
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

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setUsername("Admin");
        $admin->setEmail("admin@db.com");
        $admin->setPassword($this->hasher->hashPassword($admin, 'Admin1234'));
        $admin->setRoles(array('ROLE_ADMIN'));

        $mod = new User();
        $mod->setUsername("Moderator");
        $mod->setEmail("mod@db.com");
        $mod->setPassword($this->hasher->hashPassword($admin, 'Mod1234'));
        $mod->setRoles(array('ROLE_MOD'));

        $user = new User();
        $user->setUsername("Użytkownik");
        $user->setEmail("user@db.com");
        $user->setPassword($this->hasher->hashPassword($admin, 'User1234'));
        $user->setRoles(array('ROLE_USER'));

        $facebook = new Website();
        $facebook->setUrl("https://www.facebook.com/");
        $facebook->setShorturl($this->generateRandom());
        $facebook->setHash($this->generateRandom());
        $facebook->setStatus(1);
        $facebook->setUser($admin);
        $facebook->setVisited(0);
        $facebook->setCreatedDate(date("d-m-Y H:i:s"));
        $facebook->setInclude(1);

        $google = new Website();
        $google->setUrl("https://www.google.pl/");
        $google->setShorturl($this->generateRandom());
        $google->setHash($this->generateRandom());
        $google->setStatus(1);
        $google->setUser($admin);
        $google->setVisited(50);
        $google->setCreatedDate(date("d-m-Y H:i:s"));
        $google->setInclude(1);

        $youtube = new Website();
        $youtube->setUrl("https://www.youtube.com/");
        $youtube->setShorturl($this->generateRandom());
        $youtube->setHash($this->generateRandom());
        $youtube->setStatus(0);
        $youtube->setUser($mod);
        $youtube->setVisited(0);
        $youtube->setCreatedDate(date("d-m-Y H:i:s"));
        $youtube->setInclude(1);

        $noweLinki = [];
        for ($i = 0; $i < 6; $i++) {
            $nowyLink = new Website();
            $nowyLink->setUrl("https://nowy-link-$i.com/");
            $nowyLink->setShorturl($this->generateRandom());
            $nowyLink->setHash($this->generateRandom());
            $nowyLink->setStatus(1);
            $nowyLink->setUser($user);
            $nowyLink->setVisited(random_int(0, 1000));
            $nowyLink->setCreatedDate(date("d-m-Y H:i:s"));
            $nowyLink->setInclude(1);
            
            $noweLinki[] = $nowyLink;
        }

        foreach ($noweLinki as $nowyLink) {
            $manager->persist($nowyLink);
        }

        $setting = new Settings();
        $setting->setName("Akceptacja linków");
        $setting->setStatus(0);

        $manager->persist($admin);
        $manager->persist($mod);
        $manager->persist($user);
        $manager->persist($facebook);
        $manager->persist($google);
        $manager->persist($youtube);
        $manager->persist($setting);
        $manager->flush();
    }
}
