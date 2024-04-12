<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

class ProfileController extends AbstractController
{
    private $userRepository;
    private $entityManager;
    public function __construct(UserRepository $userRepository, ManagerRegistry $doctrine)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $doctrine->getManager();
    }

    #[Route('/profile', name: 'user_profile')]
    public function showProfile(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }
   
}