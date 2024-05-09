<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use PHPUnit\Util\Filesystem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem as Filesystemm ;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

 
/**
 * @Route("/product")
 */
class ProductController extends AbstractController
{

    private $authChecker;

    public function __construct(AuthorizationCheckerInterface $authChecker)
    {
        $this->authChecker = $authChecker;
    }
    
    private function checkAdminRole()
    {
        if (!$this->authChecker->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }
    }


    /**
     * @Route("/", name="app_product_index", methods={"GET"})
     */
    public function index(ProductRepository $productRepository): Response
    {
        $this->checkAdminRole(); 
        
        $products = $productRepository->findBy([], ['id' => 'DESC']);
        
        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }
 
    /**
     * @Route("/new", name="app_product_new", methods={"GET", "POST"})
     */
    public function new(Request $request, ProductRepository $productRepository): Response
    {
        $this->checkAdminRole();

        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if($request->files->get('product')['image']){
                $image=$request->files->get('product')['image'];
                $image_name = time()."_".$image->getClientOriginalName();
                $image->move($this->getParameter('image_directory'),$image_name);
                $product->setImage($image_name);
            }

            $productRepository->add($product, true);
            $this->addFlash('success', 'Product ' . $product->getName() . ' added successfully!!!');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_product_show", methods={"GET"})
     */
    public function show(Product $product): Response
    {
        $this->checkAdminRole();
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_product_edit", methods={"GET", "POST"})
     */

    public function edit(Request $request, Product $product, ProductRepository $productRepository): Response
    {
        $this->checkAdminRole();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if($request->files->get('product')['image']){
                $image=$request->files->get('product')['image'];
                $image_name = time()."_".$image->getClientOriginalName();
                $image->move($this->getParameter('image_directory'),$image_name);
                $product->setImage($image_name);
            }
            
            $productRepository->add($product, true);
            $this->addFlash('warning', 'Product ' . $product->getName() . ' edited successfully!!!');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }
       $product->setImage($product->getImage());
        return $this->renderForm('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

 
    /**
     * @Route("/{id}", name="app_product_delete", methods={"POST"})
     */
    public function delete(Request $request, Product $product, ProductRepository $productRepository): Response
    {
        
        $filesystem=new Filesystemm();
        $imagepath='./productimg/'.$product->getImage();
        if($filesystem->exists($imagepath)){
            $filesystem->remove($imagepath);
        }

        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $productRepository->remove($product, true);
        }
        $this->addFlash('danger', 'Product ' . $product->getName() . ' deleted successfully!!!');
        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }
}
