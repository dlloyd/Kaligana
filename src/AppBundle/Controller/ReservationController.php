<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use AppBundle\Entity\Reservation;


class ReservationController extends Controller
{
	/**
     * @Route("/create/reservation/{productId}",requirements={"productId" = "\d+"}, name="create_reservation")
     */
    public function createAction(Request $req,$productId){

    	$em = $this->getDoctrine()->getManager();
        $resa = array();
    	$prod = $em->getRepository('AppBundle:Product')->find($productId);

        if(!$prod || !$prod->getIsHidden()){

    	if($prod->getProductType()->getName()=="Activity"){
    		$form = $this->activityForm($resa);
    	}
    	else{
    		$form = $this->carLodgmentForm($resa);
    	}

    	$form->HandleRequest($req);
        if($form->isSubmitted() && $form->isValid()){
    		//add reservation to session
    		$session = $req->getSession();
    		if($session->has('reservation')){
    			$session->remove('reservation');
    		}
    		$session->set('reservation',$form->getData());

    		return $this->redirectToRoute('validate_payment_reservation',array('productId'=> $productId,));
           
        }

    	return $this->render('reservation/create.html.twig',array('form' => $form->createView(),'id'=>$productId));
        }
        else{
            return $this->render('reservation/create.html.twig',array('error'=>"Produit supprimé ou inexistant"));
        }
    }


    /**
     * @Route("/reservation/validation/{productId}",requirements={"productId" = "\d+"}, name="validate_payment_reservation")
     */
    public function validatePaymentReservationAction(Request $req,$productId){
    	
    	//if payment accepted create reservation and send mail with all informations
    	//remove reservation from session
    	//redirect to bill page
    	//else redirect to payment page with error payment
        $em = $this->getDoctrine()->getManager();
        $prod = $em->getRepository('AppBundle:Product')->find($productId);
        $session = $req->getSession();
        $em = $this->getDoctrine()->getManager();
        if(!$session->has('reservation')){
            return $this->redirectToRoute('homepage');
        }
        $resa = $this->createNewReservation($session->get('reservation'),$prod);
        $em->persist($resa);
        $em->flush();

        $amount = $resa->getQuantity()* $resa->getProduct()->getPriceUnit();

        $req->getSession()->getFlashBag()->add('notice','réservation validée: '.$resa->getId().', montant: '. $amount );
    	return $this->redirectToRoute('homepage');
    }

    /**
     * @Route("/admin/show/reservations/", name="show_all_reservations")
     */
    public function showAllAction(Request $req){

    	$em = $this->getDoctrine()->getManager();
    	$reservations = $em->getRepository('AppBundle:Reservation')->findAll();
    	return $this->render('reservation/reservations.html.twig', array('reservations' => $reservations ));
    }

    /**
     * @Route("/admin/show/res/{code}", name="show_reservation_by_code")
     */
    public function showReservationByCodeAction($code){
    	$em = $this->getDoctrine()->getManager();
    	$res = $em->getRepository('AppBundle:Reservation')->findBy(['name' => $code]);
    	if($res){
    		return $this->render('reservation/reservation.html.twig', array('reservation' => $res )); 
    	}

    	$request->getSession()->getFlashBag()->add('error','Code réservation inexistant');
        return $this->render('reservation/reservation.html.twig');
    }


    public function generateCode($reservation){  //génère code de réservation unique 
    	return null;
    }

    public function activityForm($resa){
        return $this->createFormBuilder($resa)
            ->add('quantity', IntegerType::class)
            ->add('save', SubmitType::class, array('label' => 'Enregistrer'))
            ->getForm();
    }

    public function carLodgmentForm($resa){
        return $this->createFormBuilder($resa)
            ->add('manyDays', CheckboxType::class, array(
                    'label'    => 'Plusieurs jours ?',
                    'required' => false,
            ))
            ->add('dateUnique', TextType::class,array(
                    'required' => false,
                    'attr' => array(
                    'readonly' => true,
            ),))
            ->add('dateRange', TextType::class,array(
                    'required' => false,
                    'attr' => array(
                    'readonly' => true,
                    'hidden' => true,
            ),))
            ->add('dateBegin', HiddenType::class,array(
                    'attr' => array(
                    'readonly' => true,
                    'hidden' => true,
            ),))
            ->add('dateEnd', HiddenType::class,array(
                    'attr' => array(
                    'readonly' => true,
                    'hidden' => true,
            ),))
            ->add('quantity', HiddenType::class,array(
                    'attr' => array(
                    'readonly' => true,
                    'hidden' => true,
            ),))
            ->add('save', SubmitType::class, array('label' => 'Réserver'))
            ->getForm();

    }


    public function createNewReservation($resaSession,$product){
        $resa = new Reservation();
        $resa->setProduct($product);
        $resa->setDateBegin(new \Datetime($resaSession['dateBegin']));
        $resa->setDateEnd(new \Datetime($resaSession['dateEnd']));
        $resa->setQuantity($resaSession['quantity']);
        $resa->setCode("fakecodetest");

        return $resa;
    }

    
}
