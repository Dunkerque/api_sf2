<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Movies;
use AppBundle\Form\MoviesType;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DefaultController extends FOSRestController
{
    /**
     * @Get(
     *     path = "v1/movies",
     *     name = "show_movies",
     * )
     *
     * @QueryParam(
     *     name="order",
     *     requirements="asc|desc",
     *     default="asc",
     *     description="Sort data by order asrc or desc"
     * )
     *
     * @QueryParam(
     *     name="limit",
     *     requirements="\d+",
     *     default="30",
     *     description="Max number of movies per page."
     * )
     *
     *
     * @View
     */
    public function indexAction($order, $limit) {

       $movies = $this->getDoctrine()->getRepository("AppBundle:Movies")->listWithOrder($order);

       $adapter = new \Pagerfanta\Adapter\ArrayAdapter($movies);
       $pager = new \Pagerfanta\Pagerfanta($adapter);
       $pager->setMaxPerPage($limit);

       $result = $pager->getCurrentPageResults();
       return [
           "total" => $limit,
           "count" => count($result),
           "data" => [
               $result
           ]
       ];
    }

    /**
     * @Post(
     *     path="v1/movies",
     *     name="create_movies"
     * )
     *
     * @View(statusCode=201)
     */
    public function postAction(Request $request) {

        $em = $this->getDoctrine()->getManager();

        $data   = $this->get('jms_serializer')->deserialize($request->getContent(), 'array', 'json');
        $movies = new Movies();
        $form   = $this->get('form.factory')->create(MoviesType::class, $movies);
        $form->submit($data);

        $em->persist($movies);
        $em->flush();

        return $this->view(
            $movies,
            Response::HTTP_CREATED,
            [
                'Location' => $this->generateUrl('show_movies', ['id' => $movies->getId() ], UrlGeneratorInterface::ABSOLUTE_URL )
            ]
        );
    }


    /**
     * @Delete(
     *     path = "/v1/movies/{id}",
     *     name = "delete_movies",
     *     requirements={"id" = "\d+"}
     * )
     * @View(statusCode=204)
     */
    public function deleteAction(Request $request)
    {
        $em = $this->getDoctrine();

        $movie = $em->getRepository("AppBundle:Movies")->find($request->get('id'));

        if(!$movie) {
            return $this->view(
                sprintf("Movies with id :%d doesn't exist", $request->get('id')),
                Response::HTTP_NOT_FOUND
            );
        }
        $em->getManager()->remove($movie);
        $em->getManager()->flush();
    }

}
