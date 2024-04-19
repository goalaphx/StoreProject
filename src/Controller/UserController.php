<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;

class UserController extends AbstractController
{
    private $userRepository;
    private $entityManager;

    public function __construct(UserRepository $userRepository, ManagerRegistry $doctrine)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $doctrine->getManager();
    }

    #[Route('/users', name: 'admin_user_list')]
    #[IsGranted("ROLE_ADMIN", statusCode:404, message:"Page not found")]
    public function index(): Response
    {
        $users = $this->userRepository->findAll();
        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/user/{id}/give-admin', name: 'admin_user_give_admin')]
    #[IsGranted("ROLE_ADMIN", statusCode:404, message:"Page not found")]
    public function giveAdminRights(User $user): Response
    {
        $user->setRoles(['ROLE_ADMIN']);
        $this->entityManager->flush();
        $this->addFlash('success', 'Admin rights given to user successfully.');
        return $this->redirectToRoute('admin_user_list');
    }
    
    #[Route('/user/delete/{id}', name: 'admin_user_delete')]
    #[IsGranted("ROLE_ADMIN", statusCode:404, message:"Page not found")]
    public function deleteUser(User $user) : Response
    {
        
    
        $orders = $user->getOrders();
        foreach ($orders as $order) {
            $this->entityManager->remove($order);
        }
        $this->entityManager->remove($user);
        $this->entityManager->flush();
        $this->addFlash(
            'success',
            'The User was Removed'
        );
    
        // Redirect to the user list page upon successful deletion
        return $this->redirectToRoute('admin_user_list');
    }
    
    


    #[Route('/user/edit/{id}', name: 'edit_profile')]
    public function EditProfile(User $user, Request $request): Response
    {
        
        $form = $this->createForm(ProfileType::class,$user);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $user = $form->getData();
            $profilePicFile = $form['profilepic']->getData();
        if ($profilePicFile) {
            $image_name = time() . '_' . $profilePicFile->getClientOriginalName();
            $profilePicFile->move($this->getParameter('profile_directory'), $image_name);
            $user->setProfilepic($image_name);
        }

            // Persist the entity to the database
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                'Your Profile was Updated'
            );
            return $this->redirectToRoute('user_profile');
            }
            return $this->render('profile/edit.html.twig', [
                'form' => $form
            ]);

   
}

}