<?php

namespace App\Controller;

use App\Service\TwilioService;
use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class RegistrationController extends AbstractController
{
    private $entityManager;
    private $twilioService;

    public function __construct(EntityManagerInterface $entityManager, TwilioService $twilioService)
    {
        $this->entityManager = $entityManager;
        $this->twilioService = $twilioService;
    }
   

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        if($this->getUser()){
            return $this->redirectToRoute('home');
        }
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $otp = sprintf('%06d', mt_rand(0, 999999));
                $this->twilioService->sendWhatsappOTP($user->getPhonenum(), $otp);
                $session->set('username', $user->getUsername());
                $session->set('otp', $otp);
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setVerifyStatus('Pending');
            $entityManager->persist($user);
            $entityManager->flush();

            
            return $this->redirectToRoute('home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
    #[Route('/verify', name: 'verify')]
    public function verify(Request $request, SessionInterface $session): Response
    {
        $msg = "";
        if ($session->get('otp') !== null && $session->get('username') !== null) {
            $otpFromForm = $request->request->get('otp');
            $sessionOtp = $session->get('otp');
            if (empty($otpFromForm)) {
                $msg = "";
            } else {
                if ($otpFromForm == $sessionOtp) {
                    $sessionUsername = $session->get('username');
                    $userRepository = $this->entityManager->getRepository(User::class);
                    $user = $userRepository->findOneBy(['username' => $sessionUsername]);
                    if ($user) {
                        $user->setVerifyStatus('Verified');
                        $this->entityManager->persist($user);
                        $this->entityManager->flush();
                        $msg = 'Account verified successfully.';
                        //  return $this->redirectToRoute('dashboard');
                    } else {
                        return $this->redirectToRoute('login');
                    }
                } else {
                    $msg = 'Verification code is incorrect.';
                }
            }
        } else {
            return $this->redirectToRoute('login');
        }    
        return $this->render('registration/verify.html.twig', ['message' => $msg]);
    }
    
      
   
    #[Route('/terms', name: 'terms_of_use')]
    public function terms(): Response
    {
        return $this->render('registration/terms.html.twig');
    }
    
}
