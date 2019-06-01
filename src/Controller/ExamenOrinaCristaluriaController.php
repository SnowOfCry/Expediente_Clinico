<?php

namespace App\Controller;

use App\Entity\ExamenOrinaCristaluria;
use App\Form\ExamenOrinaCristaluriaType;
use App\Repository\ExamenOrinaCristaluriaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\ExamenSolicitado;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\Session;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security as Security2;

/**
 * @Route("/examen/orina/cristaluria")
 */
class ExamenOrinaCristaluriaController extends AbstractController
{
    /**
     * @Route("/{examen_solicitado}", name="examen_orina_cristaluria_index", methods={"GET"})
     */
    public function index(ExamenOrinaCristaluriaRepository $examenOrinaCristaluriaRepository,ExamenSolicitado $examen_solicitado, Security $AuthUser): Response
    {
        return $this->render('examen_orina_cristaluria/index.html.twig', [
            'examen_orina_cristalurias' => $examenOrinaCristaluriaRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new/{examen_solicitado}", name="examen_orina_cristaluria_new", methods={"GET","POST"})
     */
    public function new(Request $request,ExamenSolicitado $examen_solicitado, Security $AuthUser): Response
    {
        $examenOrinaCristalurium = new ExamenOrinaCristaluria();
        $form = $this->createForm(ExamenOrinaCristaluriaType::class, $examenOrinaCristalurium);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($examenOrinaCristalurium);
            $entityManager->flush();

            return $this->redirectToRoute('examen_orina_cristaluria_index');
        }

        return $this->render('examen_orina_cristaluria/new.html.twig', [
            'examen_orina_cristalurium' => $examenOrinaCristalurium,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/{examen_solicitado}", name="examen_orina_cristaluria_show", methods={"GET"})
     */
    public function show(ExamenOrinaCristaluria $examenOrinaCristalurium,ExamenSolicitado $examen_solicitado, Security $AuthUser): Response
    {
        return $this->render('examen_orina_cristaluria/show.html.twig', [
            'examen_orina_cristalurium' => $examenOrinaCristalurium,
        ]);
    }

    /**
     * @Route("/{id}/{examen_solicitado}/edit", name="examen_orina_cristaluria_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, ExamenOrinaCristaluria $examenOrinaCristalurium,ExamenSolicitado $examen_solicitado, Security $AuthUser): Response
    {
        $form = $this->createForm(ExamenOrinaCristaluriaType::class, $examenOrinaCristalurium);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('examen_orina_cristaluria_index', [
                'id' => $examenOrinaCristalurium->getId(),
            ]);
        }

        return $this->render('examen_orina_cristaluria/edit.html.twig', [
            'examen_orina_cristalurium' => $examenOrinaCristalurium,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/{examen_solicitado}", name="examen_orina_cristaluria_delete", methods={"DELETE"})
     */
    public function delete(Request $request, ExamenOrinaCristaluria $examenOrinaCristalurium,ExamenSolicitado $examen_solicitado, Security $AuthUser): Response
    {
        if ($this->isCsrfTokenValid('delete'.$examenOrinaCristalurium->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($examenOrinaCristalurium);
            $entityManager->flush();
        }

        return $this->redirectToRoute('examen_orina_cristaluria_index');
    }
}