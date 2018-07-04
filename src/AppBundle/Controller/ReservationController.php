<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints as Assert;
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


    /*
    public function validatePaymentReservationAction(Request $req,$productId){
    	
    	//if payment accepted create reservation and send mail with all informations
    	//remove reservation from session
    	//redirect to bill page
    	//else redirect to payment page with error payment
        $em = $this->getDoctrine()->getManager();
        $prod = $em->getRepository('AppBundle:Product')->find($productId);
        $session = $req->getSession();
        
        if(!$session->has('reservation')){
            return $this->redirectToRoute('homepage');
        }
        $resa = $this->createNewReservation($session->get('reservation'),$prod);
        $em->persist($resa);
        $em->flush();

        $amount = $resa->getQuantity()* $resa->getProduct()->getPriceUnit();

        return $this->render('payment/success.html.twig',array('reservation'=>$resa,'amount'=>$amount,));

    }*/

    /**
     * @Route("/reservation/validation/{productId}",requirements={"productId" = "\d+"}, name="validate_payment_reservation")
     */
    public function paymentAction(Request $req,$productId){
        $em = $this->getDoctrine()->getManager();
        $prod = $em->getRepository('AppBundle:Product')->find($productId);
        $session = $req->getSession();
        
        if(!$session->has('reservation')){
            return $this->redirectToRoute('homepage');
        }
        else{
          $resaSession = $session->get('reservation');
          $amount = $resaSession['quantity']*$prod->getPriceUnit();  
          $form = $this->paymentForm();
         
          if ($req->isMethod('POST')) {
            $form->handleRequest($req);
         
            if ($form->isSubmitted() && $form->isValid() ) {
              try{
                \Stripe\Stripe::setApiKey($this->getParameter('stripe_secret_key'));
                $username = $form->get('firstname')->getData()." ".$form->get('lastname')->getData();
                $charge = \Stripe\Charge::create(array(
                  'customer' => $username,
                  'amount'   => $amount*100,
                  'currency' => 'eur',
                  'source' => $form->get('token')->getData(),
                  'receipt_email' => $form->get('email')->getData()
                ));

                $resa = $this->createNewReservation($resaSession,$prod);
                $em->persist($resa);
                $em->flush();

                //Envoyer email avec infos

                $session->remove('reservation');
                return $this->render('payment/success.html.twig',array('reservation'=>$resa,'amount'=>$amount,));

              }
              catch(\Stripe\Error\Base $e){
                $this->addFlash('warning', sprintf('Unable to take payment, %s', $e instanceof \Stripe\Error\Card ? lcfirst($e->getMessage()) : 'please try again.'));
              }
            }
          }
         
        return $this->render('payment/validation.html.twig', [
          'form' => $form->createView(),
          'stripe_public_key' => $this->getParameter('stripe_public_key'),
          'amount' => $amount,
          'product' => $prod,
          'reservation'=>$resaSession,
        ]);
        }
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

    /**
     * @Route("/resa/sess/remove/}", name="remove_reservation_session")
     */
    public function removeSessionReservationAction(Request $req){
        $session = $req->getSession();
        if($session->has('reservation')){
            $session->remove('reservation');
        }
        return $this->redirectToRoute('homepage');

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
            ->add('save', SubmitType::class, array('disabled'=>true,'label' => 'Réserver'))
            ->getForm();

    }


    public function createNewReservation($resaSession,$product){
        $resa = new Reservation();
        $resa->setProduct($product);
        $resa->setDateBegin(new \Datetime($resaSession['dateBegin']));
        $resa->setDateEnd(new \Datetime($resaSession['dateEnd']));
        $resa->setQuantity($resaSession['quantity']);
        $resa->setCode("fakecodetest");
        $resa->setCustomerFirstName($resaSession['firstname']);
        $resa->setCustomerLastName($resaSession['lastname']);

        return $resa;
    }

    public function paymentForm(){
        return $this->get('form.factory')
          ->createNamedBuilder('payment-form')
          ->add('firstname',TextType::class)
          ->add('lastname',TextType::class)
          ->add('email',EmailType::class)
          ->add('token', HiddenType::class, [
            'constraints' => [new Assert\NotBlank()],
          ])
          ->add('submit', SubmitType::class, array('label' => 'Payer'))
          ->getForm();
    }


    public function sendMailConfirmationReservation($reservation){
        $message = (new \Swift_Message('Confirmation réservation'))
        ->setFrom('kaligana@mail.com')
        ->setTo($email)
        ->setBody(
            $this->renderView(
                // app/Resources/views/Emails/registration.html.twig
                'payment/email_validation.html.twig',
                array('reservation' => $reservation)
            ),
            'text/html'
        );
        $this->get('mailer')->send($message);
    }

    
}
