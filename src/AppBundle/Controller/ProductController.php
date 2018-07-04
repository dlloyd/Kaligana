<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use AppBundle\Entity\Product;
use AppBundle\Form\ProductType;
use AppBundle\Entity\Image;

class ProductController extends Controller
{
	/**
     * @Route("/products/type/{code}", name="products_by_type")
     */
    public function productsByTypeAction($code)
    {
    	$em = $this->getDoctrine()->getManager();
    	$type = $em->getRepository('AppBundle:ProductType')->findOneBy(array('code'=>$code));

    	if(!$type){
    		return $this->redirectToRoute('homepage');
    	}

    	$title = $this->getProductTypeName($code);

        return $this->render('product/products-by-type.html.twig',array('type'=>$type,'title'=>$title,));
    }


    /**
     * @Route("/products/{id}", requirements={"id" = "\d+"}, defaults={"id" = 1}, name="get_product")
     */
    public function getProductAction($id){
    	$em = $this->getDoctrine()->getManager();
    	$prod = $em->getRepository('AppBundle:Product')->find($id);

    	if(!$prod || $prod->getIsHidden()){
    		throw new NotFoundHttpException("Page not found");
    	}

        return $this->render('product/product.html.twig',array('product'=>$prod,));
    }

    /**
     * @Route("/product/imgs/{id}", requirements={"id" = "\d+"}, defaults={"id" = 1}, name="product_images")
     */
    public function getAsyncProductImagesAction($id){
    	if($req->isXMLHttpRequest()){
    		$em = $this->getDoctrine()->getManager();
    		$prod = $em->getRepository('AppBundle:Product')->find($id);

    		if($prod){
    			return new JsonResponse(array('data'=>json_encode($prod->getImages())));
    		}
    		
    		return new JsonResponse(array('error'=>'Produit introuvable'));
    	}
    	return new Response("This is not ajax request!",400);  // json content
    }

    /**
     * @Route("/admin/add/prod", name="add_product")
     */
    public function addProductAction(Request $req){
    	$prod = new Product();
    	$form = $this->createForm(ProductType::class, $prod);
    	$form->HandleRequest($req);
    	if($form->isSubmitted() && $form->isValid()){
           $em = $this->getDoctrine()->getManager();
           $prod->setIsHidden(false);
           $em->persist($prod);
           $em->flush();
           $req->getSession()->getFlashBag()->add('notice','Produit ajouté');
           return $this->redirectToRoute('add_product_image',array('id'=>$prod->getId()));
        }

        return $this->render('product/new.html.twig', array(
            'form' => $form->createView(),
        ));

    }


    /**
     * @Route("/admin/update/prod/{id}", requirements={"id" = "\d+"}, name="update_product")
     */
    public function updateProductAction(Request $req,$id){
    	$em = $this->getDoctrine()->getManager();
    	$prod = $em->getRepository('AppBundle:Product')->find($id);
    	$form = $this->createForm(ProductType::class, $prod);

    	$form->HandleRequest($req);
    	if($form->isSubmitted() && $form->isValid()){
           $em = $this->getDoctrine()->getManager();
           $em->merge($prod);
           $em->flush();
           $req->getSession()->getFlashBag()->add('notice','Produit mis à jour');
           return $this->redirectToRoute('add_product_image',array('id'=>$id));
        }

        return $this->render('product/update.html.twig', array(
            'form' => $form->createView(),'product'=>$prod,
        ));

    }


    /**
     * @Route("/admin/add/img/product/{id}", requirements={"id" = "\d+"}, name="add_product_image")
     */
    public function addImageProductAction(Request $req,$id){
    	$em = $this->getDoctrine()->getManager();
    	$prod = $em->getRepository('AppBundle:Product')->find($id);
    	$image = new Image();
    	$image->setProduct($prod);

    	$form = $this->createFormBuilder($image)
            ->add('imageFile', FileType::class )
            ->add('isStar', CheckboxType::class, array(
			    'label'    => 'Image vedette ?',
			    'required' => false,
			))
            ->add('save', SubmitType::class, array('label' => 'Enregistrer'))
            ->getForm();

        $form->HandleRequest($req);
    	if($form->isSubmitted() && $form->isValid()){
           $em = $this->getDoctrine()->getManager();
           $em->persist($image);
           if($image->getIsStar()){
           	foreach ($prod->getImages() as $img) {
           		if($img->getIsStar())
           			$img->setIsStar(false);
           	}
           	$prod->setImgStarName($image->getName());
           	$em->merge($prod);
           }

           
           $em->flush();
           $req->getSession()->getFlashBag()->add('notice','Image ajouté');
           return $this->redirectToRoute('add_product_image',array('id'=>$id));
        }

        return $this->render('product/prod-images.html.twig', array(
            'form' => $form->createView(), 'product'=>$prod,
        ));

    }

    /**
     * @Route("/admin/product/imgstar/{imgId}", requirements={"imgId" = "\d+"}, name="change_product_image_star")
     */
    public function changeProductImageStarAction($imgId){
    	$em = $this->getDoctrine()->getManager();
    	$img = $em->getRepository('AppBundle:Image')->find($imgId);

    	if($img){
    		
    		$prod = $img->getProduct() ;
    		foreach ($prod->getImages() as $image) {
           		if($image->getIsStar())
           			$image->setIsStar(false);
           	}
           	$img->setIsStar(true);
           	$prod->setImgStarName($img->getName());
           	$em->merge($prod);
           	$em->merge($img);
           	$em->flush();
    		return $this->redirectToRoute('add_product_image',array('id'=>$prod->getId(),));
    	}

    	return $this->redirectToRoute('show_all_products');
    	
    }


    /**
     * @Route("/admin/product/img/del/{imgId}", requirements={"imgId" = "\d+"}, name="delete_product_image")
     */
    public function deleteImageProductAction($imgId){
    	$em = $this->getDoctrine()->getManager();
    	$img = $em->getRepository('AppBundle:Image')->find($imgId);


    	if($img){
    		$prod = $img->getProduct();
    		$prod->removeImage($img);
    		$em->merge($prod);
    		$em->flush();
    		$request->getSession()->getFlashBag()->add('notice','Image supprimée');
    		return $this->redirectToRoute('add_product_image',array('id'=>$prod->getId(),));
    	}
    	return $this->redirectToRoute('show_all_products');

    }


    /**
     * @Route("/admin/show/all/products/", name="show_all_products")
     */
    public function showAllProductsAction(){
    	$em = $this->getDoctrine()->getManager();
    	$prods = $em->getRepository('AppBundle:Product')->findAll();

    	return $this->render('product/products.html.twig',array('products'=>$prods,));

    }

    /**
     * @Route("/admin/del/prod/{id}", requirements={"id" = "\d+"}, name="delete_product")
     */
    public function removeProductAction(Request $req,$id){
    	$em = $this->getDoctrine()->getManager();
    	$prod = $em->getRepository('AppBundle:Product')->find($id);

    	if($prod){
    		$prod->setIsHidden(true);
    		$em->merge($prod);
    		$em->flush();
    		$req->getSession()->getFlashBag()->add('notice','Produit supprimé');

    	}
    	else{
    		$req->getSession()->getFlashBag()->add('error','Produit inexistant');
    	}
    	
    	return $this->redirectToRoute('show_all_products');
    }


    public function getProductTypeName($code){

    	switch ($code) {
    		case 'CAR':
    			$title = 'Voitures';
    			break;

    		case 'LOD':
    			$title = 'Logements';
    			break;

    		case 'ACT':
    			$title = 'Activités';
    			break;
    		
    		default:
    			# code...
    			break;
    	}
    	return $title;
    }
}
