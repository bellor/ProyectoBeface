<?php
namespace AppBundle\Twig {

// Activo 'RegistryInterface' para poder usar Doctrine
    use Symfony\Bridge\Doctrine\RegistryInterface;

    class LikeExtension extends \Twig_Extension
    {

        /* CARGAMOS DOCTRINE **************************************************************/
     // Para usar 'Doctrine' necesiatmos de 'RegistryInterface'
        protected $doctrine;

        /**
         * LikeExtension constructor.
         * @param RegistryInterface $doctrine
         */
        public function __construct(RegistryInterface $doctrine)
        {
            $this->doctrine = $doctrine;
        }

        /* DEFINIMOS NOMBRE DEL FILTRO + FUNCIÓN FILTRO ***********************************/
        /**
         * @return array
         */
        public function getFilters()
        {

        /*
       * indicamos como llamaremos al filtro `islike'
       * y que función ejecutará el filtro `numlikes`
       */
        return array(
            new \Twig_SimpleFilter('islike', array($this, 'islikeFilter')),
            new \Twig_SimpleFilter('numlikes', array($this, 'numlikesFilter'))
        );
    }

    /* DEFINIMOS LA FUNCIÓN ***********************************************************/
        /**
         * @return string
         */
        public function getName()
        {
            return 'like_extension';
        }

        /* FUNCIÓN FILTRO *****************************************************************
        /**
         * Permite comprobar si se ha marcado como me gusta a una publicación
         *
         * @param $user
         * @param $publication
         * @return bool
         */
        public function islikeFilter($user, $publication)
        {
            // cargamos el repositorio de Likes
            $like_repo = $this->doctrine->getRepository('AppBundle:Likes');
            /*
            * buscamos un registro que comparta el $user y la $publication
            * introducida en el método
            */
            $publication_liked = $like_repo->findOneBy(array(
                'user' => $user,
                'publication' => $publication
            ));

            if (!empty($publication_liked) && is_object($publication_liked)){
                $result = true;
            } else {
                $result = false;
            }

            return $result;
        }

        /* FUNCIÓN OBTENER NÚMEROS DE LIKES *****************************************************************
        /**
         * Obtención de número de likes de una publicación
         *
         * @param $publication
         * @return int
         */
        public function numlikesFilter($publication)
        {
            $likes_repo = $this->doctrine->getRepository('AppBundle:Likes');
            $publication_likes = $likes_repo->findBy(array(
                'publication' => $publication
            ));

            return count($publication_likes);
        }

    }
}
