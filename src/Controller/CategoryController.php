<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CategoryController extends AbstractController
{
    private $categoryRepository;
    private $entityManager;

    public function __construct(CategoryRepository $categoryRepository, ManagerRegistry $doctrine)
    {
        $this->categoryRepository = $categoryRepository;
        $this->entityManager = $doctrine->getManager();
    }

    #[Route('/categories', name: 'admin_category_list')]
    public function index(): Response
    {
        $categories = $this->categoryRepository->findAll();
        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('category/add', name: 'admin_category_add')]
    #[IsGranted("ROLE_ADMIN", statusCode:404, message:"Page not found")]
    public function add(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($category);
            $this->entityManager->flush();

            $this->addFlash('success', 'Category added successfully.');
            return $this->redirectToRoute('admin_category_list');
        }

        return $this->render('category/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/category/{id}/delete', name: 'admin_category_delete')]
    #[IsGranted("ROLE_ADMIN", statusCode:404, message:"Page not found")]
    public function delete(Category $category): Response
    {
        // Retrieve all products associated with the category
        $products = $category->getProducts();

        // Remove each product
        foreach ($products as $product) {
            $this->entityManager->remove($product);
        }

        // Remove the category itself
        $this->entityManager->remove($category);

        // Flush changes to the database
        $this->entityManager->flush();

        $this->addFlash('success', 'Category and associated products deleted successfully.');
        return $this->redirectToRoute('admin_category_list');
    }

}
