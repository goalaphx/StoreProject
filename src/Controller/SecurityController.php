<?php
namespace App\Controller;

use App\Form\LoginType;
use App\Service\TwilioService;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController
{
    private $entityManager;
    private $twilioService;

    public function __construct(EntityManagerInterface $entityManager, TwilioService $twilioService)
    {
        $this->entityManager = $entityManager;
        $this->twilioService = $twilioService;
    }

    #[Route('/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LoginType::class);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $repository = $entityManager->getRepository(User::class);
            $user = $repository->findOneBy(['username' => $formData['username']]);
    
            if ($user) {
                // Check if the user's status is "Verified"
                if ($user->GetVerifiyStatus() === "Verified") {
                    // Perform authentication and redirect to the homepage
                    // You need to implement the authentication logic here
                    // Example: return $this->redirectToRoute('home');
                } else {
                    // If the user's status is "Pending", redirect to the verify route
                    $WhatsappNo = $user->getPhonenum();
                    $otp = sprintf('%06d', mt_rand(0, 999999));
                    $this->twilioService->sendWhatsappOTP($WhatsappNo, $otp);
                    $session->set('username', $formData['username']);
                    $session->set('otp', $otp);
                    return $this->redirectToRoute('verify');
                }
            } else {
                $msg = "Incorrect Username/Password";
            }
        }
    
        return $this->render('security/login.html.twig', [
            'form' => $form->createView(),
            'message' => $msg ?? null,
        ]);
    }
    
    


    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
