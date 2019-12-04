<?php
namespace AppBundle\Twig {

    use Symfony\Bridge\Doctrine\RegistryInterface;

    class UserStatsExtension extends \Twig_Extension
    {
        protected $doctrine;

        /**
         * UserStatsExtension constructor.
         * @param RegistryInterface $doctrine
         */
        public function __construct(RegistryInterface $doctrine)
        {
            $this->doctrine = $doctrine;
        }

        /**
         * @return array
         */
        public function getFilters()
        {
            return array(
                new \Twig_SimpleFilter('user_stats', array($this, 'userStatsFilter'))
            );
        }

        /**
         * @return string
         */
        public function getName()
        {
            return 'user_stats_extension';
        }

        /**
         * Devuelve array con datos de actividad del usuario
         *
         * @param $user
         * @return array
         */
        public function userStatsFilter($user)
        {
            $follow_repo = $this->doctrine->getRepository('AppBundle:Follow');
            $publication_repo = $this->doctrine->getRepository('AppBundle:Publication');
            $like_repo = $this->doctrine->getRepository('AppBundle:Likes');
            $notification_repo = $this->doctrine->getRepository('AppBundle:Notification');
            $pm_repo = $this->doctrine->getRepository('AppBundle:PrivateMessage');


            $user_following = $follow_repo->findBy(array('user' => $user));
            $user_followers = $follow_repo->findBy(array('followed' => $user));
            $user_publications = $publication_repo->findBy(array('user' => $user));
            $user_likes = $like_repo->findBy(array('user' => $user));
            $notifications = $notification_repo->findBy(array(
                'user' => $user,
                'readed' => 0));
            $message = $pm_repo->findBy(array('receiver' => $user,
                'readed' => 0));

            $result = array(
                'following' => count($user_following),
                'followers' => count($user_followers),
                'publications' => count($user_publications),
                'likes' => count($user_likes),
                'notifications' => count($notifications),
                'message' => count($message)
            );

            return $result;
        }

    }
}
