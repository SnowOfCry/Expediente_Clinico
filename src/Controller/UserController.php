<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Clinica;
use App\Entity\Rol;
use App\Entity\Especialidad;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
/**
 * @Route("/user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="doctor_index", methods={"GET"})
     */
    public function indexUserDoctor(UserRepository $userRepository): Response
    {
        $em = $this->getDoctrine()->getManager();

        $RAW_QUERY = 'SELECT u.id as id,u.nombres as Nombres, u.apellidos as Apellidos,u.email as email, c.nombre_clinica as clinica, r.nombre_rol as rol FROM `user` as u,rol as r,clinica as c
            WHERE u.clinica_id = c.id AND u.rol_id = r.id AND r.nombre_rol = "ROLE_DOCTOR";';
        $statement = $em->getConnection()->prepare($RAW_QUERY);
        $statement->execute();
        $result = $statement->fetchAll();

        return $this->render('user/Doctor/index.html.twig', [
            'controller_name' => 'UserController','users' => $result
        ]);
    }

    /**
     * @Route("/new", name="doctor_new", methods={"GET","POST"})
     */
    public function newUserDoctor(Request $request): Response
    {
        $clinicasObject = $this->getDoctrine()->getRepository(Clinica::class)->findAll();
        foreach ($clinicasObject as $clin) {
            $clinicas[$clin->getId()]=$clin->getNombreClinica();
        }
        $rolObject = $this->getDoctrine()->getRepository(Rol::class)->findAll();
        foreach ($rolObject as $rol) {
            $roles[$rol->getId()]=$rol->getNombreRol();
        }

        $user = new User();
        $form = $this->createFormBuilder($user)
        ->add('nombres', TextType::class, array('attr' => array('class' => 'form-control')))
        ->add('apellidos', TextType::class,array('attr' => array('class' => 'form-control')))
        ->add('email', EmailType::class, array('attr' => array('class' => 'form-control')))
        ->add('clinica', EntityType::class, array('class' => Clinica::class,'placeholder' => 'Seleccione una clinica','choice_label' => 'nombreClinica','attr' => array('class' => 'form-control')))
        ->add('usuario_especialidades', EntityType::class, array('class' => Especialidad::class,'placeholder' => 'Seleccione las especialidades','choice_label' => 'nombreEspecialidad','multiple' => true,'expanded' => true,
            'attr' => array('class' => 'form-control')))
        ->add('guardar', SubmitType::class, array('attr' => array('class' => 'btn btn-outline-success')))
        ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rol = new Rol();
            $rol = $this->getDoctrine()->getRepository(Rol::class)->findOneByNombre('ROLE_DOCTOR');
            $entityManager = $this->getDoctrine()->getEntityManager();
            $user->setEmail($form["email"]->getData());
            $user->setPassword(password_hash(substr(md5(microtime()),1,8),PASSWORD_DEFAULT,[15]));
            $user->setNombres($form["nombres"]->getData());
            $user->setApellidos($form["apellidos"]->getData());
            $user->setRol($rol);
            $user->setClinica($form["clinica"]->getData());
            foreach ($form["usuario_especialidades"]->getData() as $especialidad) {
                $user->addUsuarioEspecialidades($especialidad);    
            }
            $entityManager->persist($user);
            $entityManager->flush();
            return $this->redirectToRoute('doctor_index');
        }

        return $this->render('user/Doctor/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_show", methods={"GET"})
     */
    public function show(User $user): Response
    {
        return $this->render('user/Doctor/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="doctor_edit", methods={"GET","POST"})
     */
    public function editUserDoctor(Request $request, User $user): Response
    {
        $form = $this->createFormBuilder($user)
        ->add('nombres', TextType::class, array('attr' => array('class' => 'form-control')))
        ->add('apellidos', TextType::class,array('attr' => array('class' => 'form-control')))
        ->add('email', EmailType::class, array('attr' => array('class' => 'form-control')))
        ->add('clinica', EntityType::class, array('class' => Clinica::class,'placeholder' => 'Seleccione una clinica','choice_label' => 'nombreClinica','attr' => array('class' => 'form-control')))
        ->add('usuario_especialidades', EntityType::class, array('class' => Especialidad::class,'placeholder' => 'Seleccione las especialidades','choice_label' => 'nombreEspecialidad','multiple' => true,'expanded' => true,
            'attr' => array('class' => 'form-control')))
        ->add('guardar', SubmitType::class, array('attr' => array('class' => 'btn btn-outline-success')))
        ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rol = new Rol();
            $rol = $this->getDoctrine()->getRepository(Rol::class)->findOneByNombre('ROLE_DOCTOR');
            $entityManager = $this->getDoctrine()->getEntityManager();
            $user->setEmail($form["email"]->getData());
            $user->setPassword(password_hash(substr(md5(microtime()),1,8),PASSWORD_DEFAULT,[15]));
            $user->setNombres($form["nombres"]->getData());
            $user->setApellidos($form["apellidos"]->getData());
            $user->setRol($rol);
            $user->setClinica($form["clinica"]->getData());

            foreach ($form["usuario_especialidades"]->getData() as $especialidad) {
                $user->addUsuarioEspecialidades($especialidad);    
            }
            $entityManager->persist($user);
            $entityManager->flush();
            return $this->redirectToRoute('doctor_index');
        }

        return $this->render('user/Doctor/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_delete", methods={"DELETE"})
     */
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('doctor_index');
    }
}



