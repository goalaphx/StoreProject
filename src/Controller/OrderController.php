<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProductRepository;
use App\Repository\OrderRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Product;
use App\Entity\Order;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class OrderController extends AbstractController
{
    private $orderRepository;
    private $entityManager;
    public function __construct(
        OrderRepository $orderRepository, ManagerRegistry $doctrine)
    {
        $this->orderRepository = $orderRepository;
        $this->entityManager = $doctrine->getManager();

    }
    #[Route('/orders', name: 'orders_list')]
    #[IsGranted("ROLE_ADMIN", statusCode:404, message:"Page not found")]
    public function index(): Response
    {
        $orders = $this->orderRepository->findAll();
        return $this->render('order/index.html.twig', [
            'orders' => $orders
        ]);
    }

    #[Route('/user/order', name: 'user_orders_list')]
    public function userOrders(): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }

        return $this->render('order/user.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
    #[Route('/store/order/{product}', name: 'order_store')]
    public function store(Product $product): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }

        $orderExists = $this->orderRepository->findOneBy([ 
            'user' => $this->getUser(),
            'pname' => $product->getName()
        ]);

        if($orderExists){
            $this->addFlash(
                'warning',
                'You Have Already Ordered this Product'
            );
            return $this->redirectToRoute('user_orders_list');
            
        }

        $order = new Order();
        $order->setPname($product->getName());
        $order->setPrice($product->getPrice());
        $order->setStatus('processing...');
        $order->setUser($this->getUser());
        
            // tell Doctrine you want to (eventually) save the Product (no queries yet)
        $this->entityManager->persist($order);

        // actually executes the queries (i.e. the INSERT query)
        $this->entityManager->flush();
        $this->addFlash(
            'success',
            'Your Order was Saved'
        );
        return $this->redirectToRoute('user_orders_list');
        
        
    }

    #[Route('/update/order/{order}/{status}', name: 'order_status_update')]
    #[IsGranted("ROLE_ADMIN", statusCode:404, message:"Page not found")]
    public function updateOrderStatus(Order $order,$status): Response
    {
        $order->setStatus($status);
        // tell Doctrine you want to (eventually) save the Product (no queries yet)
        $this->entityManager->persist($order);

        // actually executes the queries (i.e. the INSERT query)
        $this->entityManager->flush();
        $this->addFlash(
            'success',
            'Your Order Status was Updated'
        );
        return $this->redirectToRoute('orders_list');
    }
    #[Route('/update/order/{order}', name: 'order_delete')]
    #[IsGranted("ROLE_ADMIN", statusCode:404, message:"Page not found")]
    public function deleteOrder(Order $order): Response
    {
        // tell Doctrine you want to (eventually) save the Product (no queries yet)
        $this->entityManager->remove($order);

        // actually executes the queries (i.e. the INSERT query)
        $this->entityManager->flush();
        $this->addFlash(
            'success',
            'Your Order was Deleted'
        );
        return $this->redirectToRoute('orders_list');
    }
}
