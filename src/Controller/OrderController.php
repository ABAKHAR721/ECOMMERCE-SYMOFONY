<?php

namespace App\Controller;

use App\Entity\Order;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\OrderType; 
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/order")
 */
class OrderController extends AbstractController
{
    
    private $orderRepository;
    private $entityManager;
    private $authChecker;

    public function __construct(
        OrderRepository $orderRepository,
        ManagerRegistry $doctrine,
        AuthorizationCheckerInterface $authChecker)
    {
        $this->orderRepository=$orderRepository;
        $this->entityManager=$doctrine->getManager();
        $this->authChecker = $authChecker;
        
    }
    
 

    private function checkAdminRole()
    {
        if (!$this->authChecker->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException();
        }
    }


    /**
     * @Route("/", name="app_order_index", methods={"GET"})
     */
    public function index(OrderRepository $orderRepository): Response
    {
        $this->checkAdminRole();
        return $this->render('order/index.html.twig', [
            'orders' => $orderRepository->findAll(),
        ]);
    }

    /**
     * @Route("/user", name="app_user_order_list", methods={"GET"})
     */
    public function userOrders(ProductRepository $productRepository): Response
    {    // Check if user is logged in
        
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        // Get EntityManager
        $entityManager = $this->entityManager;

        // Get cart items for the current user
        $cartItems = $entityManager->getRepository(Order::class)
            ->findBy([
                'user' => $this->getUser(),
                'ettat' => 'cart'
            ]);
            
           
        return $this->render('order/userorder.html.twig', [
            'cartItems' => $cartItems,
            'user' => $this->getUser(),
            'products'=> $productRepository->findAll()
             
        ]);
    } 

    

    /**
    * @Route("/client/{product}", name="app_order_client", methods={"GET"})
    */
    public function store( Product $product): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }
        
        $order = new Order();
        $order->setPname($product->getName());
        $order->setPrice($product->getPrice());
        
        $order->setUser($this->getUser());

        $this->entityManager->persist($order);

        $this->entityManager->flush();
        $this->addFlash('success','order passed successfully !!!');
        return $this->redirectToRoute('app_user_order_list');
    }


    /**
     * @Route("/new", name="app_order_new", methods={"GET", "POST"})
     */
    public function new(Request $request, OrderRepository $orderRepository): Response
    {
        $this->checkAdminRole();
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $orderRepository->add($order, true);

            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('order/new.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_order_show", methods={"GET"})
     */
    public function show(Order $order): Response
    {
        $this->checkAdminRole();
        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_order_edit", methods={"GET", "POST"})
     */
   

    public function edit(Request $request, Order $order, OrderRepository $orderRepository): Response
    {
        $this->checkAdminRole();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $orderRepository->add($order, true);
            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }
        // dd($request);
        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
        ]);
    }
    

    /**
     * @Route("/{id}", name="app_order_delete", methods={"POST"})
     */
    public function delete(Request $request, Order $order, OrderRepository $orderRepository): Response
    {

        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->request->get('_token'))) {
            $orderRepository->remove($order, true);
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    } 

     /**
     * @Route("/user/{id}", name="app_order_user_delete", methods={"POST"})
     */
    public function deleteuserorder(Request $request, Order $order, OrderRepository $orderRepository): Response
    {

        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->request->get('_token'))) {
            $orderRepository->remove($order, true);
        }

        return $this->redirectToRoute('app_user_order_list', [], Response::HTTP_SEE_OTHER);
    } 


/**
 * @Route("/cart/checkout", name="cart_checkout")
 */
public function checkout(): Response
{
    // Check if user is logged in
    if (!$this->getUser()) {
        return $this->redirectToRoute('app_login');
    }

    // Get EntityManager
    $entityManager = $this->entityManager;

    // Get cart items for the current user
    $cartItems = $entityManager->getRepository(Order::class)
        ->findBy([
            'user' => $this->getUser(),
            'ettat' => 'cart'
        ]);

    if (empty($cartItems)) {
        $this->addFlash('warning', 'Your cart is empty.');
        return $this->redirectToRoute('app_home');
    }

    // Change the status of cart items to 'ordered' when checking out
    foreach ($cartItems as $cartItem) {
        $cartItem->setEttat('ordered');
        // $cartItem->setStatus('Processing...');
    }

    $entityManager->flush();

    $this->addFlash(
        'success',
        'Checkout successful!'
    );

    return $this->redirectToRoute('app_user_order_list');
}



/**
 * @Route("/order/history", name="order_history", methods={"GET"})
 */
public function orderHistory(EntityManagerInterface $entityManager,ProductRepository $productRepository): Response
{
    // Check if user is logged in
    if (!$this->getUser()) {
        return $this->redirectToRoute('app_login');
    }

    // Get order history for the current user
    $orderHistory = $entityManager->getRepository(Order::class)
        ->findBy([
            'user' => $this->getUser(),
            'ettat' => 'ordered'
        ]);

    return $this->render('order/orders.html.twig', [
        'orderHistory' => $orderHistory,
        'user' => $this->getUser(),
        'products'=>$productRepository->findAll()
    ]);
}
}
