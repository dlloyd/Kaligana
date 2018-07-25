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
    		$form = $this->activityForm($resa,$productId);
    	}
    	else{
    		$form = $this->carLodgmentForm($resa,$productId);
    	}

    	$form->HandleRequest($req);
        if($form->isSubmitted() && $form->isValid()){
    		//add reservation to session
    		$session = $req->getSession();
    		if($session->has('reservations')){
                $reservations = $session->get('reservations');
                array_push($reservations, $form->getData()) ;
    			$session->set('reservations',$reservations);
    		}
            else{
                $reservations = array();
                array_push($reservations, $form->getData());
                $session->set('reservations',$reservations);
            }
    		

    		return $this->redirectToRoute('validate_payment_reservation');
           
        }

    	return $this->render('reservation/create.html.twig',array('form' => $form->createView(),'id'=>$productId));
        }
        else{
            return $this->render('reservation/create.html.twig',array('error'=>"Produit supprimé ou inexistant"));
        }
    }



    /**
     * @Route("/reservation/validation/", name="validate_payment_reservation")
     */
    public function paymentAction(Request $req){
        $em = $this->getDoctrine()->getManager();
        $resaAmount = array();
        
        $session = $req->getSession();
        
        if(!$session->has('reservations')){
            return $this->redirectToRoute('homepage');
        }
        else{
          $resasSession = $session->get('reservations');
          $amount = 0;
          foreach ($resasSession as $resa) {
              $prod = $em->getRepository('AppBundle:Product')->find($resa['productId']);
              $resaAmount[$prod->getId()] =  $resa['quantity']*$prod->getPriceUnit();
              $resaProduct[$prod->getId()] = $prod;
              $amount += $resaAmount[$prod->getId()];
          }
            
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

                $resa = $this->createNewReservation($resasSession);
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
          'resaAmount' => $resaAmount,
          'resaProduct' => $resaProduct,
          'reservations'=>$resasSession,
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
    public function removeSessionReservationsAction(Request $req){
        $session = $req->getSession();
        if($session->has('reservations')){
            $session->remove('reservations');
        }
        return $this->redirectToRoute('homepage');

    }

    /**
     * @Route("/resa/sess/remove/prod/{id}}",requirements={"id" = "\d+"}, name="remove_reservation_product")
     */
    public function removeSessionReservationProductAction(Request $req,$id){
        $session = $req->getSession();
        if($session->has('reservations')){
            $newReservations = array();
            foreach ($session->get('reservations') as $resa) {
                if($resa['productId'] != $id){
                    array_push($newReservations,$resa);
                }
            }
            $session->set('reservations',$newReservations);
            if(empty($newReservations)){
                $session->remove('reservations');
            }

            
        }
        return $this->redirectToRoute('validate_payment_reservation');

    }




    public function generateCode($reservation){  //génère code de réservation unique 
    	return null;
    }

    public function activityForm($resa,$productId){
        return $this->createFormBuilder($resa)
            ->add('quantity', IntegerType::class,array('data'=>1))
            ->add('productId', HiddenType::class,array(
                    'data' =>$productId,
                    'attr' => array(
                    'readonly' => true,
                    'hidden' => true,
            ),))
            ->add('save', SubmitType::class, array('label' => 'Enregistrer'))
            ->getForm();
    }

    public function carLodgmentForm($resa,$productId){
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
            ->add('productId', HiddenType::class,array(
                    'data' =>$productId,
                    'attr' => array(
                    'readonly' => true,
                    'hidden' => true,
            ),))
            ->add('save', SubmitType::class, array('disabled'=>true,'label' => 'Réserver'))
            ->getForm();

    }


    public function createNewReservation($resasSession){
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
          ->add('submit', SubmitType::class, array('label' => 'Confirmer et Payer'))
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
