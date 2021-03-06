<?php

namespace App\Controller;

use App\Entity\Camilla;
use App\Entity\Habitacion;
use App\Repository\CamillaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Security\Core\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security as Security2;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Session\Session;
/**
 * @Route("/camilla")
 */
class CamillaController extends AbstractController
{
    /**
     * @Route("/{habitacion}", name="camilla_index", methods={"GET"})
     * @Security2("is_authenticated()")
     * @Security2("user.getIsActive()", statusCode=412, message="Su cuenta está inactiva")
     * @Security2("is_granted('ROLE_PERMISSION_INDEX_CAMILLA')")
     */
    public function index(CamillaRepository $camillaRepository,Security $AuthUser, Habitacion $habitacion): Response
    {
        //VALIDACION DE REGISTROS UNICAMENTE DE MI CLINICA SI NO SOY ROLE_SA
        if($AuthUser->getUser()->getRol()->getNombreRol() != 'ROLE_SA'){
            if($AuthUser->getUser()->getClinica()->getId() == $habitacion->getSala()->getClinica()->getId()){
                
            }else{
                $this->addFlash('fail','Error, este registro puede que no exista o no le pertenece');
                return $this->redirectToRoute('habitacion_index',array('clinica'=>$AuthUser->getUser()->getClinica()->getId()));
            }
        }
        
        $em = $this->getDoctrine()->getManager();
        $RAW_QUERY = "SELECT * FROM camilla WHERE habitacion_id = ".$habitacion->getId().";";
        $statement = $em->getConnection()->prepare($RAW_QUERY);
        $statement->execute();
        $result = $statement->fetchAll();

        return $this->render('camilla/index.html.twig', [
            'camillas' => $result,
            'user'      => $AuthUser,
            'habitacion' => $habitacion,
        ]);
    }

    /**
     * @Route("/new/{habitacion}", name="camilla_new", methods={"GET","POST"})
     * @Security2("is_authenticated()")
     * @Security2("user.getIsActive()", statusCode=412, message="Su cuenta está inactiva")
     * @Security2("is_granted('ROLE_PERMISSION_NEW_CAMILLA')")
     */
    public function new(Request $request,Habitacion $habitacion,Security $AuthUser): Response
    {
        //VALIDACION DE REGISTROS UNICAMENTE DE MI CLINICA SI NO SOY ROLE_SA
        if($AuthUser->getUser()->getRol()->getNombreRol() != 'ROLE_SA'){
            if($AuthUser->getUser()->getClinica()->getId() == $habitacion->getSala()->getClinica()->getId()){
            }else{
                $this->addFlash('fail','Error, este registro puede que no exista o no le pertenece');
                return $this->redirectToRoute('habitacion_index',array('clinica'=>$AuthUser->getUser()->getClinica()->getId()));
            }
        }

        $editar=false;
        $camilla = new Camilla();
        $form = $this->createFormBuilder($camilla)
        ->add('numeroCamilla', IntegerType::class, array('attr' => array('class' => 'form-control','min'=>1)))
        ->add('guardar', SubmitType::class, array('attr' => array('class' => 'btn btn-outline-success')))
        ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if($form["numeroCamilla"]->getData() != ""){
                $entityManager = $this->getDoctrine()->getManager();
                //VALIDACION PARA COMPROBAR SI ESE NUMERO DE CAMILLA YA EXISTE
                $em = $this->getDoctrine()->getManager();
                $RAW_QUERY = "SELECT c.* FROM camilla as c, habitacion as h, sala as s, clinica as cli 
                WHERE c.habitacion_id=h.id AND h.sala_id=s.id AND s.clinica_id = cli.id 
                AND cli.id =".$habitacion->getSala()->getClinica()->getId().
                " AND numero_camilla = ".$form["numeroCamilla"]->getData().";";
                $statement = $em->getConnection()->prepare($RAW_QUERY);
                $statement->execute();
                $result = $statement->fetchAll();
                if($result == null){
                    $camilla->setNumeroCamilla($form["numeroCamilla"]->getData());
                    $this->addFlash('success','Camilla añadida con éxito');
                    $camilla->setHabitacion($habitacion);
                    $entityManager->persist($camilla);
                    $entityManager->flush();
                    return $this->redirectToRoute('camilla_index',['habitacion' => $habitacion->getId()]);
                }else{
                    $this->addFlash('fail','Error, esta camilla ya está asignada por favor ingrese una nueva camilla');
                }
                //FIN VALIDACION PARA COMPROBAR SI ESE NUMERO DE CAMILLA YA EXISTE       
            }else{
                $this->addFlash('fail','Error, el número de camilla no puede estar vacío, por favor ingrese el número de camilla');
            }
        }
        return $this->render('camilla/new.html.twig', [
            'camilla' => $camilla,
            'habitacion'    => $habitacion,
            'editar'        => $editar,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/{habitacion}", name="camilla_show", methods={"GET"})
     * @Security2("is_authenticated()")
     * @Security2("user.getIsActive()", statusCode=412, message="Su cuenta está inactiva")
     * @Security2("is_granted('ROLE_PERMISSION_SHOW_CAMILLA')")
     */
    public function show(Camilla $camilla, Habitacion $habitacion,Security $AuthUser): Response
    {
        
        $conn = $this->getDoctrine()->getManager()->getConnection();           
        $sql = 'SELECT ca.habitacion_id as habitacion_id, c.nombre_clinica as nombre_clinica, s.nombre_sala as nombre_sala, h.numero_habitacion as numero_habitacion FROM camilla as ca, habitacion as h, sala as s, clinica as c
                WHERE ca.habitacion_id = h.id AND
                h.sala_id = s.id              AND
                s.clinica_id = c.id           AND
                h.id =  :habitacion';
        $stmt = $conn->prepare($sql); 
        $stmt->execute(array('habitacion' => $habitacion->getId()));
        $result= $stmt->fetch();
        //VALIDACION DE REGISTROS UNICAMENTE DE MI CLINICA SI NO SOY ROLE_SA
        if($AuthUser->getUser()->getRol()->getNombreRol() != 'ROLE_SA'){
            if($AuthUser->getUser()->getClinica()->getId() == $camilla->getHabitacion()->getSala()->getClinica()->getId() && $AuthUser->getUser()->getClinica()->getId() == $habitacion->getSala()->getClinica()->getId() && $camilla->getHabitacion()->getId() == $habitacion->getId() ){

            }else{
                $this->addFlash('fail','Error, este registro puede que no exista o no le pertenece');
                return $this->redirectToRoute('habitacion_index',array('clinica'=>$AuthUser->getUser()->getClinica()->getId()));
            }
        }
        return $this->render('camilla/show.html.twig', [
            'camilla' => $camilla,
            'habitacion'    => $result,
        ]);
    }

    /**
     * @Route("/{id}/{habitacion}/edit", name="camilla_edit", methods={"GET","POST"})
     * @Security2("is_authenticated()")
     * @Security2("user.getIsActive()", statusCode=412, message="Su cuenta está inactiva")
     * @Security2("is_granted('ROLE_PERMISSION_EDIT_CAMILLA')")
     */
    public function edit(Request $request, Camilla $camilla, Habitacion $habitacion, Security $AuthUser): Response
    {   
        //VALIDACION DE REGISTROS UNICAMENTE DE MI CLINICA SI NO SOY ROLE_SA
        if($AuthUser->getUser()->getRol()->getNombreRol() != 'ROLE_SA'){
            if($AuthUser->getUser()->getClinica()->getId() == $camilla->getHabitacion()->getSala()->getClinica()->getId() && $AuthUser->getUser()->getClinica()->getId() == $habitacion->getSala()->getClinica()->getId() && $camilla->getHabitacion()->getId() == $habitacion->getId() ){

            }else{
                $this->addFlash('fail','Error, este registro puede que no exista o no le pertenece');
                return $this->redirectToRoute('habitacion_index',array('clinica'=>$AuthUser->getUser()->getClinica()->getId()));
            }
        }

        $editar=true;
        $form = $this->createFormBuilder($camilla)
        ->add('guardar', SubmitType::class, array('attr' => array('class' => 'btn btn-outline-success')))
        ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            if($request->request->get('numeroCambio') != ""){
                //VALIDACION PARA COMPROBAR SI ESE NUMERO DE CAMILLA YA EXISTE
                $em = $this->getDoctrine()->getManager();
                $RAW_QUERY = "SELECT c.* FROM camilla as c, habitacion as h, sala as s, clinica as cli 
                WHERE c.habitacion_id=h.id AND h.sala_id=s.id AND s.clinica_id = cli.id 
                AND cli.id =".$habitacion->getSala()->getClinica()->getId().
                " AND numero_camilla = ".$request->request->get('numeroCambio').";";
                $statement = $em->getConnection()->prepare($RAW_QUERY);
                $statement->execute();
                $result = $statement->fetchAll();
                if($result != null){
                    //VERIFICACION PARA OBTENER LA CAMILLA QUE SE VA A TRANSFERIR A LA HABITACION ACTUAL
                        $RAW_QUERY = "SELECT c.* FROM camilla as c, habitacion as h, sala as s, clinica as cli 
                        WHERE c.habitacion_id=h.id AND h.sala_id=s.id AND s.clinica_id = cli.id 
                        AND cli.id =".$habitacion->getSala()->getClinica()->getId().
                        " AND numero_camilla = ".$request->request->get('numeroCambio').";";
                        $statement = $em->getConnection()->prepare($RAW_QUERY);
                        $statement->execute();
                        $result = $statement->fetchAll();
                        $camillaIntercambio = $this->getDoctrine()->getRepository(Camilla::class)->find($result[0]['id']);
                        $camillaIntercambio->setNumeroCamilla($camilla->getNumeroCamilla());
                        $camilla->setNumeroCamilla($result[0]['numero_camilla']);
                        $entityManager->persist($camilla);
                        $entityManager->flush();
                        $mensaje = 'Camilla Modificada con éxito, se intercambiaron camillas entre la habitacion: '.$camillaIntercambio->getHabitacion()->getNumeroHabitacion().' de la sala: '.$camillaIntercambio->getHabitacion()->getSala()->getNombreSala().' con la habitacion: '.$camilla->getHabitacion()->getNumeroHabitacion().' de la sala :'.$camilla->getHabitacion()->getSala()->getNombreSala();
                        $this->addFlash('success',$mensaje);
                        return $this->redirectToRoute('camilla_index',['habitacion' => $habitacion->getId()]);
                        //FIN VERIFICACION PARA OBTENER LA CAMILLA QUE SE VA A TRANSFERIR A LA HABITACION ACTUAL
                }else{
                    $this->addFlash('fail','Error, esta camilla no existe porfavor escoja una camilla existente.');
                }
                //FIN VALIDACION PARA COMPROBAR SI ESE NUMERO DE CAMILLA YA EXISTE
            }else{
                $this->addFlash('fail','el numero de camilla con la que desea intercambiar no puede ir vacío');
            }
        }
        return $this->render('camilla/edit.html.twig', [
            'camilla' => $camilla,
            'habitacion'    => $habitacion,
            'editar'        => $editar,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/{habitacion}", name="camilla_delete", methods={"DELETE"})
     * @Security2("is_authenticated()")
     * @Security2("user.getIsActive()", statusCode=412, message="Su cuenta está inactiva")
     * @Security2("is_granted('ROLE_PERMISSION_DELETE_CAMILLA')")
     */
    public function delete(Request $request, Camilla $camilla, Habitacion $habitacion, Security $AuthUser): Response
    {
        //VALIDACION DE REGISTROS UNICAMENTE DE MI CLINICA SI NO SOY ROLE_SA
        if($AuthUser->getUser()->getRol()->getNombreRol() != 'ROLE_SA'){
            if($AuthUser->getUser()->getClinica()->getId() == $camilla->getHabitacion()->getSala()->getClinica()->getId() && $AuthUser->getUser()->getClinica()->getId() == $habitacion->getSala()->getClinica()->getId() && $camilla->getHabitacion()->getId() == $habitacion->getId() ){

            }else{
                $this->addFlash('fail','Error, este registro puede que no exista o no le pertenece');
                return $this->redirectToRoute('habitacion_index',array('clinica'=>$AuthUser->getUser()->getClinica()->getId()));
            }
        }
        if ($this->isCsrfTokenValid('delete'.$camilla->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($camilla);
            $entityManager->flush();
        }
        $this->addFlash('success','Camilla eliminada con éxito');
        return $this->redirectToRoute('camilla_index',['habitacion' => $habitacion->getId()]);
    }
}
