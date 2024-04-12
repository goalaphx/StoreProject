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
    public function login(Request $request, EntityManagerInterface $entityManager, AuthenticationUtils $authenticationUtils, SessionInterface $session): Response
    {
        $msg = "";
        
        // Retrieve error from authentication utils
        $error = $authenticationUtils->getLastAuthenticationError();
        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $form = $this->createForm(LoginType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $repository = $entityManager->getRepository(User::class);
            $login = $repository->findOneBy([
                'username' => $formData['username'],
                'password' => $formData['password'],
            ]);

            if ($login !== null) {
                if ($login->GetVerifiyStatus() == "Pending") {
                    $WhatsappNo = $login->getphonenum();
                    $otp = sprintf('%06d', mt_rand(0, 999999));
                    $this->twilioService->sendWhatsappOTP($WhatsappNo, $otp);
                    $session->set('username', $formData['username']);
                    $session->set('otp', $otp);
                    return $this->redirectToRoute('verify');
                } else {
                    $msg = "Your account has been verified, and you will be redirected to your dashboard page.";
                    // return $this->redirectToRoute('dashboard');
                }
            } else {
                $msg = "Incorrect Username/Password";
            }
        }

        return $this->render('security/login.html.twig', [
            'form' => $form->createView(),
            'last_username' => $lastUsername,
            'error' => $error,
            'message' => $msg,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
