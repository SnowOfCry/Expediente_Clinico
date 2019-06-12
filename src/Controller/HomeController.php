<?php

namespace App\Controller;

use App\Entity\Expediente;
use App\Entity\Habitacion;
use App\Entity\Sala;
use App\Entity\Clinica;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security as Security2;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Session\Session;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        return $this->render('base2.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }


    /**
     * @Route("/home", name="home")
     * @Security2("user.getIsActive()", statusCode=412, message="Su cuenta está inactiva")
     */
    public function home(Security $AuthUser)
    {   
        return $this->render('home/index.html.twig', [
        'controller_name' => 'HomeController',
        'user'            => $AuthUser,
        ]);
    }

    /**
     * @Route("/agenda", name="calendario_trabajo")
     * @Security2("user.getIsActive()", statusCode=412, message="Su cuenta está inactiva")
     */
    public function calendarioTrabajo(Security $AuthUser)
    {   
        return $this->render('cita/calendarClinica.html.twig', [
        'controller_name' => 'HomeController',
        'user'            => $AuthUser,
        ]);
    }
    /**
     * @Route("/cambioContrasena", name="app_cambio")
     * @Security2("is_authenticated()")
     * @Security2("user.getIsActive()", statusCode=412, message="Su cuenta está inactiva")
     */
    public function cambio_contrasena(Request $request,Security $AuthUser): Response
    {   
        $userForm = new User();
        $form = $this->createFormBuilder($userForm)
        ->add('guardar', SubmitType::class, array('attr' => array('class' => 'btn btn-outline-success')))
        ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ( $request->request->get('password_actual') != "" && $request->request->get('nueva_password') != "" && $request->request->get('confirmar_nueva_password') != "" ) {
                if(password_verify($request->request->get('password_actual'), $AuthUser->getUser()->getPassword() ) ){
                    if($request->request->get('nueva_password') == $request->request->get('confirmar_nueva_password') ){
                        $user = $this->getDoctrine()->getRepository(User::class)->find($AuthUser->getUser()->getId());
                        $password = $request->request->get('nueva_password');
                        $hash = password_hash($password, PASSWORD_DEFAULT,[15]);
                        $user->setPassword($hash);
                        $entityManager = $this->getDoctrine()->getEntityManager();
                        $entityManager->persist($user);
                        $entityManager->flush();
                        $this->addFlash('success', 'Contraseña modificada con éxito');
                        return $this->redirectToRoute('home');
                    }else{
                        $this->addFlash('fail', 'Un problema ha ocurrido, la nueva contraseña debe coincidir con la confirmacion de contraseña');
                    }
                }else{
                    $this->addFlash('fail', 'Un problema ha ocurrido, compruebe que la contraseña actual sea correcta');
                }
            }else{
                $this->addFlash('fail', 'Un problema ha ocurrido verifique si todos los campos han sido completados');
            }
        }

        return $this->render('user/passwordUpdate.html.twig', [
        'controller_name' => 'HomeController',
        'form'            => $form->createView(),
        ]);
    }

    /**
     * @Route("/salaClinica", name="ajax_cargaSalas", methods={"GET","POST"})
     * @Security2("is_authenticated()")
     */
    public function cargaSalas(Security $AuthUser, Request $request)
    { 
        $expediente = $this->getDoctrine()->getRepository(Expediente::class)->find($request->request->get('expediente'));
        $em = $this->getDoctrine()->getManager();
        $RAW_QUERY="SELECT * FROM sala WHERE clinica_id=".$expediente->getUsuario()->getClinica()->getId().";";
        $statement = $em->getConnection()->prepare($RAW_QUERY);
        $statement->execute();
        $result = $statement->fetchAll();
        return $this->json($result);
    }

    /**
     * @Route("/habitacionesClinica", name="ajax_cargaHabitaciones", methods={"GET","POST"})
     * @Security2("is_authenticated()")
     */
    public function cargaHabitaciones(Security $AuthUser, Request $request)
    { 
        $sala = $this->getDoctrine()->getRepository(Sala::class)->find($request->request->get('sala'));
        $expediente = $this->getDoctrine()->getRepository(Expediente::class)->find($request->request->get('expediente'));
        if($sala->getClinica()->getId() == $expediente->getUsuario()->getClinica()->getId()){
            $clinica = $this->getDoctrine()->getRepository(Clinica::class)->find($sala->getClinica()->getId());
            $em = $this->getDoctrine()->getManager();
            //SI EL EXPEDIENTE QUE YO LLEVO CON LA SALA QUE YO LLEVO DEBEN COINCIDIR EN EL ID DE LA CLINICA
            $RAW_QUERY="SELECT DISTINCT habitacion.* FROM habitacion,clinica,sala, camilla WHERE
                        camilla.habitacion_id = habitacion.id           AND
                        habitacion.sala_id    = sala.id                 AND
                        sala.clinica_id       = clinica.id              AND
                        clinica.id            = ".$clinica->getId()."   AND
                        sala.id               = ".$sala->getId()."      AND
                        camilla.id NOT IN (
                            SELECT camilla_id FROM ingresado WHERE fecha_salida IS NULL
                        );";
            $statement = $em->getConnection()->prepare($RAW_QUERY);
            $statement->execute();
            $result = $statement->fetchAll();
            return $this->json($result);
        }else{
            $this->addFlash('fail','Error, este expediente no es valido');
            $this->redirectToRoute('expediente_index');
        }
        
    }


    /**
     * @Route("/camillasClinica", name="ajax_cargaCamillas", methods={"GET","POST"})
     * @Security2("is_authenticated()")
     */
    public function cargaCamillas(Security $AuthUser, Request $request)
    { 
        $sala = $this->getDoctrine()->getRepository(Sala::class)->find($request->request->get('sala'));
        $expediente = $this->getDoctrine()->getRepository(Expediente::class)->find($request->request->get('expediente'));
        $habitacion = $this->getDoctrine()->getRepository(Habitacion::class)->find($request->request->get('habitacion'));
        if($sala->getClinica()->getId() == $expediente->getUsuario()->getClinica()->getId() && $habitacion->getSala()->getId()== $sala->getId()){
            $clinica = $this->getDoctrine()->getRepository(Clinica::class)->find($sala->getClinica()->getId());
            $em = $this->getDoctrine()->getManager();
            //SI EL EXPEDIENTE QUE YO LLEVO CON LA SALA QUE YO LLEVO DEBEN COINCIDIR EN EL ID DE LA CLINICA
            $RAW_QUERY="SELECT DISTINCT camilla.* FROM habitacion,clinica,sala, camilla WHERE
                        camilla.habitacion_id = habitacion.id            AND
                        habitacion.sala_id    = sala.id                  AND
                        sala.clinica_id       = clinica.id               AND
                        clinica.id            = ".$clinica->getId()."    AND
                        sala.id               = ".$sala->getId()."       AND
                        habitacion.id         = ".$habitacion->getId()." AND
                        camilla.id NOT IN (
                            SELECT camilla_id FROM ingresado WHERE fecha_salida IS NULL
                        );";
            $statement = $em->getConnection()->prepare($RAW_QUERY);
            $statement->execute();
            $result = $statement->fetchAll();
            return $this->json($result);
        }else{
            $this->addFlash('fail','Error, este expediente no es valido');
            $this->redirectToRoute('expediente_index');
        }
        
    }

    /**
     * @Route("/doctoresClinica", name="ajax_cargaDoctores", methods={"GET","POST"})
     * @Security2("is_authenticated()")
     */
    public function cargaDoctores(Security $AuthUser, Request $request)
    { 

        if($request->request->get('sala')!="") {
            
            if($request->request->get('habitacion')!=""){
                
                if($request->request->get('expediente')!=""){


                    $sala = $this->getDoctrine()->getRepository(Sala::class)->find($request->request->get('sala'));
                    $expediente = $this->getDoctrine()->getRepository(Expediente::class)->find($request->request->get('expediente'));
                    $habitacion = $this->getDoctrine()->getRepository(Habitacion::class)->find($request->request->get('habitacion'));
                    
                    if($sala->getClinica()->getId() == $expediente->getUsuario()->getClinica()->getId() && $habitacion->getSala()->getId()== $sala->getId()){

                        if ($request->request->get('emergencia')!=""){
                            if ($request->request->get('emergencia') == 1) {
                                $clinica = $this->getDoctrine()->getRepository(Clinica::class)->find($sala->getClinica()->getId());
                                $em = $this->getDoctrine()->getManager();
                                //SI EL EXPEDIENTE QUE YO LLEVO CON LA SALA QUE YO LLEVO DEBEN COINCIDIR EN EL ID DE LA CLINICA
                                $RAW_QUERY="SELECT u.id as id ,CONCAT(p.primer_nombre,' ',IFNULL(p.segundo_nombre,' '),' ',p.primer_apellido,' ',IFNULL(p.segundo_apellido,' ')) as nombre_completo FROM `user` as u ,`rol` as r, `clinica` as c, `persona` as p  WHERE u.clinica_id = c.id AND u.emergencia = 1 AND r.nombre_rol='ROLE_DOCTOR' AND u.persona_id = p.id AND u.rol_id = r.id  and u.clinica_id=".$sala->getClinica()->getId().";";
                                $statement = $em->getConnection()->prepare($RAW_QUERY);
                                $statement->execute();
                                $result = $statement->fetchAll();
                                return $this->json($result);
                                
                            }else{
                                $clinica = $this->getDoctrine()->getRepository(Clinica::class)->find($sala->getClinica()->getId());
                                $em = $this->getDoctrine()->getManager();
                                //SI EL EXPEDIENTE QUE YO LLEVO CON LA SALA QUE YO LLEVO DEBEN COINCIDIR EN EL ID DE LA CLINICA
                                $RAW_QUERY="SELECT u.id as id ,CONCAT(p.primer_nombre,' ',IFNULL(p.segundo_nombre,' '),' ',p.primer_apellido,' ',IFNULL(p.segundo_apellido,' ')) as nombre_completo FROM `user` as u ,`rol` as r, `clinica` as c, `persona` as p WHERE u.clinica_id = c.id AND u.planta = 1 AND r.nombre_rol='ROLE_DOCTOR' AND u.persona_id = p.id AND u.rol_id = r.id  and u.clinica_id=".$sala->getClinica()->getId().";";
                                $statement = $em->getConnection()->prepare($RAW_QUERY);
                                $statement->execute();
                                $result = $statement->fetchAll();
                                return $this->json($result);
                            }
                        }
                        else{
                            $this->addFlash('fail','Error, el estado del ingreso no puede estar vacio');
                            $this->redirectToRoute('expediente_index');

                        }
                        


                    }else{
                        $this->addFlash('fail','Error, este expediente no es valido');
                        $this->redirectToRoute('expediente_index');
                    }

                }else{
                    $this->addFlash('fail', 'Error, no se selecciono ningun expediente');
                    $this->redirectToRoute('expediente_index');
                }

            }else{
                $this->addFlash('fail', 'Error, no se selecciono ninguna habitacion');
                $this->redirectToRoute('expediente_index');
            }

        }else{
            $this->addFlash('fail', 'Error, no se selecciono ninguna sala');
            $this->redirectToRoute('expediente_index');
        }     

    }


    /**
     * @Route("/medicoGeneral", name="ajax_cargaGeneral", methods={"GET","POST"})
     * @Security2("is_authenticated()")
     */
    public function cargaGeneral(Security $AuthUser, Request $request)
    { 
        if(empty($AuthUser->getUser()->getClinica())){
            $em = $this->getDoctrine()->getManager();
            $RAW_QUERY="SELECT u.id as id ,CONCAT(p.primer_nombre,' ',IFNULL(p.segundo_nombre,' '),' ',p.primer_apellido,' ',IFNULL(p.segundo_apellido,' ')) as nombre_completo FROM `user` as u ,`rol` as r, `persona` as p  WHERE u.usuario_especialidades_id IS NULL AND u.emergencia = false AND u.planta = false AND u.persona_id = p.id AND r.nombre_rol='ROLE_DOCTOR' AND u.rol_id = r.id  and u.clinica_id=".$request->request->get('clinica').";";
            $statement = $em->getConnection()->prepare($RAW_QUERY);
            $statement->execute();
            $result = $statement->fetchAll();
            return $this->json($result);
        }else{
            $em = $this->getDoctrine()->getManager();
            $RAW_QUERY="SELECT u.id as id ,CONCAT(p.primer_nombre,' ',IFNULL(p.segundo_nombre,' '),' ',p.primer_apellido,' ',IFNULL(p.segundo_apellido,' ')) as nombre_completo FROM `user` as u ,`rol` as r, `persona` as p  WHERE u.usuario_especialidades_id IS NULL AND u.emergencia = false AND u.planta = false AND u.persona_id = p.id AND r.nombre_rol='ROLE_DOCTOR' AND u.rol_id = r.id  and u.clinica_id=".$AuthUser->getUser()->getClinica()->getId().";";
            $statement = $em->getConnection()->prepare($RAW_QUERY);
            $statement->execute();
            $result = $statement->fetchAll();
            return $this->json($result);
        }

    }

    /**
     * @Route("/medicoEspecialidad", name="ajax_cargaEspecialidad", methods={"GET","POST"})
     * @Security2("is_authenticated()")
     */
    public function cargaEspecialidad(Security $AuthUser, Request $request)
    { 
        if($request->request->get('especialidad')!=""){
            if(empty($AuthUser->getUser()->getClinica())){
                $em = $this->getDoctrine()->getManager();
                $RAW_QUERY="SELECT u.id as id ,CONCAT(p.primer_nombre,' ',IFNULL(p.segundo_nombre,' '),' ',p.primer_apellido,' ',IFNULL(p.segundo_apellido,' ')) as nombre_completo FROM `user` as u ,`rol` as r, `persona` as p  WHERE u.emergencia = false AND u.planta = false AND u.persona_id = p.id AND u.usuario_especialidades_id =".$request->request->get('especialidad')." AND r.nombre_rol='ROLE_DOCTOR' AND u.rol_id = r.id AND u.clinica_id=".$request->request->get('clinica').";";
                $statement = $em->getConnection()->prepare($RAW_QUERY);
                $statement->execute();
                $result = $statement->fetchAll();
                return $this->json($result);
            }else{
                $em = $this->getDoctrine()->getManager();
                $RAW_QUERY="SELECT u.id as id ,CONCAT(p.primer_nombre,' ',IFNULL(p.segundo_nombre,' '),' ',p.primer_apellido,' ',IFNULL(p.segundo_apellido,' ')) as nombre_completo FROM `user` as u ,`rol` as r, `persona` as p  WHERE u.emergencia = false AND u.planta = false AND u.persona_id = p.id AND u.usuario_especialidades_id =".$request->request->get('especialidad')." AND r.nombre_rol='ROLE_DOCTOR' AND u.rol_id = r.id AND u.clinica_id=".$AuthUser->getUser()->getClinica()->getId().";";
                $statement = $em->getConnection()->prepare($RAW_QUERY);
                $statement->execute();
                $result = $statement->fetchAll();
                return $this->json($result);
            }
        }else{
            if(empty($AuthUser->getUser()->getClinica())){
                $em = $this->getDoctrine()->getManager();
                $RAW_QUERY="SELECT u.id as id ,CONCAT(p.primer_nombre,' ',IFNULL(p.segundo_nombre,' '),' ',p.primer_apellido,' ',IFNULL(p.segundo_apellido,' ')) as nombre_completo  FROM `user` as u ,`rol` as r, `persona` as p   WHERE u.emergencia = false AND u.planta = false AND u.persona_id = p.id AND u.usuario_especialidades_id IS NULL AND r.nombre_rol='ROLE_DOCTOR' AND u.rol_id = r.id  and u.clinica_id=".$request->request->get('clinica').";";
                $statement = $em->getConnection()->prepare($RAW_QUERY);
                $statement->execute();
                $result = $statement->fetchAll();
                return $this->json($result);
            }else{
                $em = $this->getDoctrine()->getManager();
                $RAW_QUERY="SELECT u.id as id ,CONCAT(p.primer_nombre,' ',IFNULL(p.segundo_nombre,' '),' ',p.primer_apellido,' ',IFNULL(p.segundo_apellido,' ')) as nombre_completo FROM `user` as u ,`rol` as r, `persona` as p  WHERE u.emergencia = false AND u.planta = false AND u.persona_id = p.id AND u.usuario_especialidades_id IS NULL AND r.nombre_rol='ROLE_DOCTOR' AND u.rol_id = r.id  and u.clinica_id=".$AuthUser->getUser()->getClinica()->getId().";";
                $statement = $em->getConnection()->prepare($RAW_QUERY);
                $statement->execute();
                $result = $statement->fetchAll();
                return $this->json($result);
            }
        }
       

    }

    /**
     * @Route("/cupos", name="ajax_cupos", methods={"GET","POST"})
     * @Security2("is_authenticated()")
     */
    public function cupos(Security $AuthUser, Request $request)
    { 
        $em = $this->getDoctrine()->getManager();
        $RAW_QUERY="SELECT r2.id as id , r2.intervalo as intervalo,r2.cantidad as cantidad,
                        (CASE
                            WHEN r2.cantidad >= 1 THEN 'No'
                            ELSE 'Si'
                        END) as disponibilidad FROM 
                        (SELECT r.id, r.intervalo, sum(r.cantidad) as cantidad FROM (
                            SELECT 0 as id,'00:00:00-00:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 0 as id,'00:30:00-01:00:00' AS intervalo, 0 as cantidad UNION
                             
                            SELECT 1 as id,'01:00:00-01:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 1 as id,'01:30:00-02:00:00' AS intervalo, 0 as cantidad UNION
                             
                            SELECT 2 as id,'02:00:00-02:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 2 as id,'02:30:00-03:00:00' AS intervalo, 0 as cantidad UNION
                              
                            SELECT 3 as id,'03:00:00-03:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 3 as id,'03:30:00-04:00:00' AS intervalo, 0 as cantidad UNION
                              
                            SELECT 4 as id,'04:00:00-04:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 4 as id,'04:30:00-05:00:00' AS intervalo, 0 as cantidad UNION
                            
                            SELECT 5 as id,'05:00:00-05:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 5 as id,'05:30:00-06:00:00' AS intervalo, 0 as cantidad UNION
                             
                            SELECT 6 as id,'06:00:00-06:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 6 as id,'06:30:00-07:00:00' AS intervalo, 0 as cantidad UNION
                              
                            SELECT 7 as id,'07:00:00-07:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 7 as id,'07:30:00-08:00:00' AS intervalo, 0 as cantidad UNION
                              
                            SELECT 8 as id,'08:00:00-08:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 8 as id,'08:30:00-09:00:00' AS intervalo, 0 as cantidad UNION
                              
                            SELECT 9 as id,'09:00:00-09:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 9 as id,'09:30:00-10:00:00' AS intervalo, 0 as cantidad UNION
                              
                            SELECT 10 as id,'10:00:00-10:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 10 as id,'10:30:00-11:00:00' AS intervalo, 0 as cantidad UNION 
                              
                            SELECT 11 as id,'11:00:00-11:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 11 as id,'11:30:00-12:00:00' AS intervalo, 0 as cantidad UNION
                             
                            SELECT 12 as id,'12:00:00-12:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 12 as id,'12:30:00-13:00:00' AS intervalo, 0 as cantidad UNION
                             
                            SELECT 13 as id,'13:00:00-13:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 13 as id,'13:30:00-14:00:00' AS intervalo, 0 as cantidad UNION
                              
                            SELECT 14 as id,'14:00:00-14:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 14 as id,'14:30:00-15:00:00' AS intervalo, 0 as cantidad UNION
                              
                            SELECT 15 as id,'15:00:00-15:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 15 as id,'15:30:00-16:00:00' AS intervalo, 0 as cantidad UNION
                            
                            SELECT 16 as id,'16:00:00-16:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 16 as id,'16:30:00-17:00:00' AS intervalo, 0 as cantidad UNION
                             
                            SELECT 17 as id,'17:00:00-17:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 17 as id,'17:30:00-18:00:00' AS intervalo, 0 as cantidad UNION
                              
                            SELECT 18 as id,'18:00:00-18:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 18 as id,'18:30:00-19:00:00' AS intervalo, 0 as cantidad UNION
                              
                            SELECT 19 as id,'19:00:00-19:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 19 as id,'19:30:00-20:00:00' AS intervalo, 0 as cantidad UNION
                              
                            SELECT 20 as id,'20:00:00-20:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 20 as id,'20:30:00-21:00:00' AS intervalo, 0 as cantidad UNION
                              
                            SELECT 21 as id,'21:00:00-21:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 21 as id,'21:30:00-22:00:00' AS intervalo, 0 as cantidad UNION
                              
                            SELECT 22 as id,'22:00:00-22:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 22 as id,'22:30:00-23:00:00' AS intervalo, 0 as cantidad UNION
                             
                            SELECT 23 as id,'23:00:00-23:30:00' AS intervalo, 0 as cantidad UNION
                            SELECT 23 as id,'23:30:00-00:00:00' AS intervalo, 0 as cantidad UNION
                             
                            SELECT HOUR(fecha_reservacion) as id ,
                                (
									CASE
									WHEN MINUTE(TIME(fecha_reservacion)) BETWEEN  0 AND 29 THEN
										CONCAT(LPAD(HOUR(TIME(fecha_reservacion)),2,'0'), ':00:00-', LPAD(HOUR(TIME(fecha_reservacion)),2,'0'), ':30:00')
									ELSE
										CONCAT(LPAD(HOUR(TIME(fecha_reservacion)),2,'0'), ':30:00-', LPAD(HOUR(TIME(fecha_reservacion))+1,2,'0'), ':00:00')
									END
								) 
                            as intervalo, count(*) FROM `cita` WHERE usuario_id=".$request->request->get('user')
                            ." AND fecha_reservacion BETWEEN '".$request->request->get('cita')["fechaReservacion"]." 00:00:00' AND '"
                            .$request->request->get('cita')["fechaReservacion"]." 23:59:59' GROUP BY intervalo) as r GROUP BY r.intervalo) 
                            as r2 GROUP BY r2.intervalo;";
        $statement = $em->getConnection()->prepare($RAW_QUERY);
        $statement->execute();
        $result = $statement->fetchAll();
        return $this->json($result);
    }

    
}
