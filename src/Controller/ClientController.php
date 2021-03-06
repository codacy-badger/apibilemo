<?php

namespace App\Controller;

use App\Entity\Client;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



/**
 * @Route("/api")
 */

class ClientController extends AbstractController
{
     /**
     * @Route("/clients/{page<\d+>?1}", name="list_client", methods={"GET"})
     * 
     * @SWG\Tag(name="Client")
     * @SWG\Response(
     *     response=200,
     *     description="Returns the list of clients",
     *     @SWG\Schema(
     *         type="array",
     *         example={},
     *         @SWG\Items(ref=@Model(type=Client::class, groups={"full"}))
     *     )
     * )
     * 
     */
    public function index(Request $request,ClientRepository $clientRepository, SerializerInterface $serializer)
    {
        $page = $request->query->get('page');
        if(is_null($page) || $page < 1) {
            $page = 1;
        }
        $limit = 10;
        $clients = $clientRepository->findAllClients($page, $limit);

        $data = $serializer->serialize($clients, 'json', [
            'groups' => ['list']
        ]);

        return new Response($data, 200, [
            'Content-Type' => 'application/json'
        ]);
    }


     /**
     * @Route("/clients/{id}", name="show_client", methods={"GET"})
     * 
     * @SWG\Tag(name="Client")
     * @SWG\Response(
     *     response=200,
     *     description="Returns the informations of a client",
     *     @SWG\Schema(
     *         type="array",
     *         example={},
     *         @SWG\Items(ref=@Model(type=Client::class, groups={"full"}))
     *     )
     * )
     * 
     * @IsGranted("ROLE_ADMIN")
     */
    public function show(Client $client, ClientRepository $clientRepository,SerializerInterface $serializer)
    {
        $client = $clientRepository->find($client->getId());
        $data = $serializer->serialize($client, 'json', [
            'groups' => ['show']
        ]);

        return new Response($data, 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * @Route("/clients", name="add_client", methods={"POST"})
     * @SWG\Tag(name="Client")
     * @SWG\Response(
     *     response=200,
     *     description="Add a new client",
     *     @SWG\Schema(
     *         type="array",
     *         example={"first_name": "fname", "last_name": "lname", "email": "example@email.com"},
     *         @SWG\Items(ref=@Model(type=Client::class, groups={"full"}))
     *     )
     * )
     */
    public function new(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $client = $serializer->deserialize($request->getContent(), Client::class, 'json');
        $errors = $validator->validate($client);

        if(count($errors)) {
            $errors = $serializer->serialize($errors, 'json');
            return new Response($errors, 500, [
                'Content-Type' => 'application/json'
            ]);
        }
        $client->setUser($this->getUser());
        $entityManager->persist($client);
        $entityManager->flush();
        $data = [
            'status' => 201,
            'message' => 'Le client a bien été ajouté'
        ];
        return new JsonResponse($data, 201);
    }

}
