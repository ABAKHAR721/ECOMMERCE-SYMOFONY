<?php

namespace App\Controller;
use App\Entity\Categorie;
use App\Entity\Product;
use App\Repository\CategorieRepository;
use App\Repository\ProductRepository;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{ 
    /**
     * @Route("/", name="app_home")
     */
    public function index(ProductRepository $productRepository,CategorieRepository $categorieRepository,OrderRepository $orders): Response
    {
        $categories= $categorieRepository->findAll();
        $products = $productRepository->findBy([], ['id' => 'DESC']);   
        return $this->render('home/index.html.twig', [
            'products' => $products,
            'categories'=>$categories,
            'orders'=>$orders,
        ]);
    }
    /**
     * @Route("/panier/{id}", name="app_panier")
     */
    public function panier( Product $produits ){

        return $this->render('home/panier.html.twig', [
         'message'=>"hadi hya lpanier",
         'produits'=>$produits
        ]);

    } 

     /**
     * @Route("/homeproduit/{id}", name="app_Hproduit")
     */
    public function Hproduit( Product $product ){

        return $this->render('home/homeproduit.html.twig', [
         'product'=>$product
        ]);

    }
    /**
     * @Route("/hcategorie/{id}", name="app_Hcategorie")
     */
    
    public function products(Categorie $categorie): Response
{
    $name = strtoupper($categorie->getName());
    $products = $categorie->getProducts();
    
    return $this->render('home/Hcategorie.html.twig', [
        'products' => $products,
        'categorie' => $categorie,
        'NAME' => $name,
    ]);
}
   

    /**
     * @Route("/hproduit/{id}", name="app_hproduit", methods={"GET"})
     */
    public function show(Product $product): Response
    {
        return $this->render('home/hproduit.html.twig', [
            'product' => $product,
        ]);
    }
  

}
