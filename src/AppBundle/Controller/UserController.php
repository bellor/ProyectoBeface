<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response; // Permite usar el método Response
use Symfony\Component\HttpFoundation\Session\Session; // Permite usar sesiones
use Symfony\Component\Routing\Annotation\Route;

use AppBundle\Entity\User; // Da acceso a la Entidad Usuario
use AppBundle\Form\RegisterType; // Da acceso al Formulario RegisterType
use AppBundle\Form\UserType; // Da acceso al Formulario UserType

class UserController extends Controller
{
    /* OBJETO SESSIÓN
 * Para activar las sesiones inicializamos la variable e incluimos
 * en ella el objeto Session()
 * No olvidar dar acceso al componenete de Symfony
 * Session() permitirá usar los mensajes FLASHBAG
 */

    private $session;

    /**
     * UserController constructor.
     */
    public function __construct()
    {
        $this->session = new Session();
    }


    /* MÉTODO PARA EL LOGIN *******************************************************************/
    /**
     * Autentificacion de usuarios usando el servicio de Symfony
     *
     * @param Request $request
     * @return Response
     */
    public function loginAction(Request $request)
    {
        // si el usuario esta logueado se redirecciona a /home
        if (is_object($this->getUser())) {
            return $this->redirect('home');
        }

        $authenticationUtils = $this->get('security.authentication_utils');
        $error = $authenticationUtils->getLastAuthenticationError();
        // informacion de usuario que falla en autentificacion
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('AppBundle:User:login.html.twig', array(
            'last_username' => $lastUsername,
            'error' => $error
        ));
    }


    /* MÉTODO PARA EL REGISTRO DE USUARIO *****************************************************/
    /**
     * Carga vista con formulario definido en clase RegisterType
     * Configura datos de formulario en objeto User y el password cifrado
     * Usa session para poder mostrar mensajes en el proceso de registro.
     *
     * @param Request $request
     * @return Response
     */
    public function registerAction(Request $request)
    {
        // si el usuario esta logueado se redirecciona a /home
        if (is_object($this->getUser())) {
            return $this->redirect('home');
        }

        $user = new User();// Creamos un nuevo objeto User
        $form = $this->createForm(RegisterType::class, $user);

        /*
     * Creamos el formulario a partir de la clase RegisterType,
     * le pasaremos la variable User
     * hay que declarar la clase RegisterType arriba
     */
        $form->handleRequest($request);
        // procesado de envio de form de registro
        if ($form->isSubmitted()) {
            if ($form->isValid()) {

                $em = $this->getDoctrine()->getManager();
                // $user_repo = $em->getRepository("AppBundle:User");

                $query = $em->createQuery('SELECT u FROM AppBundle:User u WHERE u.email = :email OR u.nick = :nick')
                ->setParameter('email', $form->get("email")->getData())
                ->setParameter('nick', $form->get("nick")->getData());

                $user_isset = $query->getResult();

                if (count($user_isset) == 0) {

                    // codificacion de password
                    $factory = $this->get("security.encoder_factory");
                    $encoder = $factory->getEncoder($user);
                    $password = $encoder->encodePassword($form->get("password")->getData(), $user->getSalt());
                    // subimos los datos usando los setters
                    // configurar datos restantes del objeto User
                    $user->setPassword($password);
                    $user->setRole("ROLE_USER");
                    $user->setImage(null);

                    $em->persist($user);
                    $flush = $em->flush();//guardar en BD

                    if ($flush == null) {
                        $status = "Te has registrado correctamente";

                        $this->session->getFlashBag()->add("status", $status);
                        return $this->redirect("login");
                    } else {
                        $status = "Error al registrar";
                    }

                } else {
                    $status = "El usuario ya existe !!";
                }

            } else {
                $status = "No te has registrado correctamente !!";
            }

            $this->session->getFlashBag()->add("status", $status);
        }

        // crea vista con formulario
        return $this->render('AppBundle:User:register.html.twig', array(
            "form" => $form->createView()
        ));
    }


    /* MÉTODO PARA LA CONSULTA AJAX (¿EXISTE EL NICK DE REGISTRO?) ****************************/
    /**
     * Método que recoge el dato nick del form de registro via AJAX
     * comprueba si nick esta en uso o no , devuelve una respuesta http con un string
     *
     * @param Request $request
     * @return Response
     */
    public function nickTestAction(Request $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if (!$isAjax) {
            return $this->redirect("register");
        }

        $nick = $request->get("nick");// Guardamos dentro de la variable $nick el dato que nos llega por POST

        // Busco dentro de la BD el dato
        $em = $this->getDoctrine()->getManager();
        $user_repo = $em->getRepository("AppBundle:User");
        $user_isset = $user_repo->findOneBy(array("nick" => $nick));

        $result = "used";
        if (count($user_isset) >= 1 && is_object($user_isset)) {
            $result = "used";
        } else {
            $result = "unused";
        }

        return new Response($result);
    }


    /* MÉTODO PARA CONFIGURAR EL PERFIL DE USUARIO ********************************************/
    /**
     * Carga vista con formulario definido en clase UserType
     * Configura datos de formulario en objeto User de la session
     * Comprueba datos de email y nick antes de guardar datos
     * Guarda imagen y configura dato de imagen
     * Usa session para poder mostrar mensajes en el proceso de registro.
     *
     * @param Request $request
     * @return Response
     */
    public function editUserAction(Request $request)
    {
        $user = $this->getUser(); //carga los datos de usuario logueado
        $user_image = $user->getImage(); // imagen antigua
        $form = $this->createForm(UserType::class, $user);

         /* Enlazamos la información de la request cuando nosotros enviamos el formulario
         sobreescribiendo el objeto $user*/
         $form->handleRequest($request);
        // procesado de envio de form de registro
         if ($form->isSubmitted()) {
            if ($form->isValid()) {

                $em = $this->getDoctrine()->getManager();

                // Hacemos la consulta
                $query = $em->createQuery('SELECT u FROM AppBundle:User u WHERE u.email = :email OR u.nick = :nick')
                ->setParameter('email', $form->get("email")->getData())
                ->setParameter('nick', $form->get("nick")->getData());

                $user_isset = $query->getResult();

                // si los datos nuevos (email o nick) del form de edicion de perfil
                // en la base de datos no hay otro email ni nick igual
                // o si los datos de session (email y nick) son iguales a los que hay en (BD y datos form)
                if (count($user_isset) == 0 || ($user->getEmail() == $user_isset[0]->getEmail() && $user->getNick() == $user_isset[0]->getNick())) {

                    // upload file
                    $file = $form["image"]->getData();

                    if (!empty($file) && $file != null) {
                        $ext = $file->guessExtension(); // obtencion de extension
                        if ($ext == 'jpg' || $ext == 'jpeg' || $ext == 'png' || $ext == 'gif') {
                            $file_name = $user->getId().'_imgprofile_'.time().'.'.$ext;
                            $user_media_route = 'uploads/media/'.$user->getId().'_usermedia';
                            $file->move($user_media_route, $file_name);

                            $user->setImage($file_name);
                        }
                    } else {
                        $user->setImage($user_image);
                    }

                    $em->persist($user);// persistimos los datos dentro de Doctirne
                    $flush = $em->flush();//guardar en BD

                    if ($flush == null) {
                        $status = "Has modificado tus datos correctamente";
                    } else {
                        $status = "No se han podido modificar tus datos correctamente";
                    }

                } else {
                    $status = "Hay ya un usuario existente con el email o nick";
                }

            } else {
                $status = "No se han modificado tus datos correctamente";
            }

            $this->session->getFlashBag()->add("status", $status);
            return $this->redirect('my-data');
        }

        return $this->render('AppBundle:User:edit_user.html.twig', array(
            "form" => $form->createView()
        ));
    }

    /* MÉTODO PARA LISTAR USUARIOS */
    /**
     * Carga lista de usuarios y los pasa al objeto que pagina los resultados
     * la paginación de resultados se pasa a la vista
     *
     * @param Request $request
     * @return Response
     */
    public function usersAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $dql = "SELECT u FROM AppBundle:User u ORDER BY u.id ASC";
        $query = $em->createQuery($dql);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // parametro request de paginacion y en que num de pagina empieza
            5 //numero de registros por paginas
        );

        return $this->render('AppBundle:User:users.html.twig', array(
            'users' => $pagination
        ));

    }

    /* MÉTODO PARA BUSCAR USUARIOS ************************************************************/
    /**
     * Realiza búsqueda con el parametro request y
     * muestra resultados en vista users
     *
     * @param Request $request
     * @return Response
     */
    public function searchAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $search = trim($request->query->get('search', null));


        if ($search == null) {
            return $this->redirect($this->generateUrl('home_publications'));
        }

        $dql = "SELECT u FROM AppBundle:User u 
        WHERE u.name LIKE :search 
        OR u.surname LIKE :search 
        OR u.nick LIKE :search ORDER BY u.id ASC";
        $query = $em->createQuery($dql)->setParameter('search', "%$search%");

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // parametro request de paginacion y en que num de pagina empieza
            5 //numero de registros por paginas
        );

        return $this->render('AppBundle:User:users.html.twig', array(
            'users' => $pagination
        ));

    }

    /* MÉTODO PARA MOSTRAR EL PERFIL DE USUARIO ***********************************************/
    /**
     * Carga vista con datos del perfil de un usuario mostrando sus publicaciones
     *
     * @param Request $request
     * @param null $nick
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function profileAction(Request $request, $nick = null)
    {
        $em = $this->getDoctrine()->getManager();

        if($nick != null) {
            $user_repo = $em->getRepository('AppBundle:User');
            $user = $user_repo->findOneBy(array(
                'nick' => $nick
            ));
        } else {
            $user = $this->getUser();
        }

        if(empty($user) || !is_object($user)) {
            return $this->redirect($this->generateUrl('home_publications'));
        }

        $user_id = $user->getId();
        $dql = "SELECT p FROM AppBundle:Publication p WHERE p.user = $user_id ORDER BY p.id DESC";
        $query = $em->createQuery($dql);

        // Iniciamos el paginador
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // parametro request de paginacion y en que num de pagina empieza
            5 //numero de registros por paginas
        );

        return $this->render('AppBundle:User:profile.html.twig', array(
            'user' => $user,
            'publications' => $pagination
        ));
    }

    /* MÉTODO PARA CONFIGURAR EL PERFIL DE USUARIO ***********************************************/
     /**
     * 
     *
     * @param Request $request
     * @param null $nick
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
     public function configureUserAction(Request $request)
     {
        $em = $this->getDoctrine()->getManager();

        $user_repo = $em->getRepository('AppBundle:User');

        $user = $this->getUser();


        return $this->render('AppBundle:User:admin_user.html.twig', array(
            'users' => $user_repo->findAll()
        ));
    }


    /* MÉTODO PARA CONFIGURAR EL PERFIL DE USUARIO ********************************************/
    /**
     * Carga vista con formulario definido en clase UserType
     * Configura datos de formulario en objeto User de la session
     * Comprueba datos de email y nick antes de guardar datos
     * Guarda imagen y configura dato de imagen
     * Usa session para poder mostrar mensajes en el proceso de registro.
     *
     * @param Request $request
     * @return Response
     */
    public function editUpdateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $user_image = $user->getImage(); // imagen antigua
        $user = $em->getRepository('AppBundle:User')->findOneBy(array(
            'id' => $id
        ));;

        $form = $this->createForm(UserType::class, $user);

         /* Enlazamos la información de la request cuando nosotros enviamos el formulario
         sobreescribiendo el objeto $user*/
         $form->handleRequest($request);
        // procesado de envio de form de registro
         if ($form->isSubmitted()) {
            if ($form->isValid()) {

                $em = $this->getDoctrine()->getManager();

                // Hacemos la consulta
                $query = $em->createQuery('SELECT u FROM AppBundle:User u WHERE u.email = :email OR u.nick = :nick')
                ->setParameter('email', $form->get("email")->getData())
                ->setParameter('nick', $form->get("nick")->getData());

                $user_isset = $query->getResult();

                // si los datos nuevos (email o nick) del form de edicion de perfil
                // en la base de datos no hay otro email ni nick igual
                // o si los datos de session (email y nick) son iguales a los que hay en (BD y datos form)
                if (count($user_isset) == 0 || ($user->getEmail() == $user_isset[0]->getEmail() && $user->getNick() == $user_isset[0]->getNick())) {

                    // upload file
                    $file = $form["image"]->getData();

                    if (!empty($file) && $file != null) {
                        $ext = $file->guessExtension(); // obtencion de extension
                        if ($ext == 'jpg' || $ext == 'jpeg' || $ext == 'png' || $ext == 'gif') {
                            $file_name = $user->getId().'_imgprofile_'.time().'.'.$ext;
                            $user_media_route = 'uploads/media/'.$user->getId().'_usermedia';
                            $file->move($user_media_route, $file_name);

                            $user->setImage($file_name);
                        }
                    } else {
                        $user->setImage($user_image);
                    }

                    $em->persist($user);// persistimos los datos dentro de Doctirne
                    $flush = $em->flush();//guardar en BD

                    if ($flush == null) {
                        $status = "Has modificado tus datos correctamente";
                    } else {
                        $status = "No se han podido modificar tus datos correctamente";
                    }

                } else {
                    $status = "Hay ya un usuario existente con el email o nick";
                }

            } else {
                $status = "No se han modificado tus datos correctamente";
            }

            $this->session->getFlashBag()->add("status", $status);
            
        }


        return $this->render('AppBundle:User:update.html.twig', array(
            "form" => $form->createView()
        ));    

    }


    public function deleteUserAction($id)
    {

            $em = $this->getDoctrine()->getManager();// Extraemos el entity manager

            $user = $this->getUser();
            $user_image = $user->getImage(); // imagen antigua
            $user = $em->getRepository('AppBundle:User')->findOneBy(array(
                'id' => $id
            ));;
    /*
     * Creamos la condición que permita borrar los usuarios
     */

    if($user->getId()) {
             // Eliminamos el objeto dentro de doctrine
        $em->remove($user);
            // persistimos la eliminación dentro de la bD
        $flush = $em->flush();

            // preparamos los mensajes informativos
        if ($flush == null){
            $status = 'El usuario se ha borrado correctamente';
        } else {
            $status = 'El usuario no se ha borrado';
        }
    } else {
        $status = 'El usuario no se ha borrado';
    }

        // para usar el objeto response es necesario cargarlo al ppio del controlador
    return new Response($status);
}


}