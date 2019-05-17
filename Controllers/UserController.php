<?php

namespace AppBundle\Controller;

use AppBundle\Entity\HouseSearchParams;
use AppBundle\Entity\PaymentTransaction;
use AppBundle\Entity\User;
use AppBundle\Form\HouseFilterType;
use AppBundle\Form\ProfileType;
use AppBundle\Service\HouseSearchParamsArrayMapper;
use AppBundle\Service\SiteSection;
use AppBundle\Service\ValidationUtil;
use Http\Client\Exception\TransferException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class UserController extends Controller
{
    /**
     * @return string premium|marketplace|services
     */
    protected abstract function getSiteSection();

    public function indexAction()
    {
        $user = $this->getUser();
        $favorites = $this->getDoctrine()
                ->getRepository('AppBundle:House')
                ->getFavoritesByUser($user, $this->getSiteSection());

        return [
            'user' => $user,
            'favorites' => array_slice($favorites, 0, 3)
        ];
    }

    public function favoritesAction()
    {
        $user = $this->getUser();
        $favorites = $this->getDoctrine()
                ->getRepository('AppBundle:House')
                ->getFavoritesByUser($user, $this->getSiteSection());

        return [
            'user' => $user,
            'favorites' => $favorites
        ];
    }

    public function searchProfileAction()
    {
        $user = $this->getUser();
        $searchParams = $this->getDoctrine()
                ->getRepository('AppBundle:HouseSearchParams')
                ->findForUser($user, $this->getSiteSection());

        return [
            'user' => $user,
            'locations' => $this->get('app.location_search')->getLocations(),
            'search_profile' => HouseSearchParamsArrayMapper::toSimpleArray($searchParams)
        ];
    }

    public function updateSearchProfileAction(Request $request)
    {
        $params = $this->getDoctrine()
                ->getRepository('AppBundle:HouseSearchParams')
                ->findForUser($this->getUser(), $this->getSiteSection());

        $form = $this->createHouseSearchForm($params);
        $form->handleRequest($request);

        if ($form->isValid()) {
            HouseSearchParamsArrayMapper::mergeWithArray($params, $form->getData());
            $params->setEnabled(true);
            $em = $this->getDoctrine()->getManager();
            $em->persist($params);
            $em->flush();
            $this->addFlash('success', 'flash.search_profile_updated');
            return new JsonResponse();
        }

        return new JsonResponse(['errors' => ValidationUtil::getFormValidationMessages($form)], 400);
    }

    public function showProfileAction()
    {
        /* @var $user User */
        $user = $this->getUser();
        $localeKeys = array_column($this->getParameter('cms.languages'), 'code');
        $localeValues = array_column($this->getParameter('cms.languages'), 'name');
        $locales = array_combine($localeKeys, $localeValues);
        $searchParamsPremium = $this->getDoctrine()
                ->getRepository('AppBundle:HouseSearchParams')
                ->findForUser($user, SiteSection::SECTION_PREMIUM);
        $searchParamsMarketplace = $this->getDoctrine()
                ->getRepository('AppBundle:HouseSearchParams')
                ->findForUser($user, SiteSection::SECTION_MARKETPLACE);

        return [
            'user' => $user,
            'available_locales' => $locales,
            'user_data' => [
                'fullName' => $user->getFullName(),
                'streetName' => $user->getStreetName(),
                'streetNumber' => $user->getStreetNumber(),
                'floor' => $user->getFloor(),
                'side' => $user->getSide(),
                'postCode' => $user->getPostCode(),
                'city' => $user->getCity(),
                'email' => $user->getEmail(),
                'phone' => $user->getPhone(),
                'phomeMobile' => $user->getPhoneMobile(),
                'subscribedToNewsletter' => $user->getSubscribedToNewsletter(),
                'preferredLocale' => $user->getPreferredLocale(),
                'premiumSearchProfileEnabled' => $searchParamsPremium->getEnabled(),
                'marketplaceSearchProfileEnabled' => $searchParamsMarketplace->getEnabled(),
            ]
        ];
    }

    public function transactionsAction()
    {
        $user = $this->getUser();
        $transactions = $this->getDoctrine()
                ->getRepository('AppBundle:PaymentTransaction')
                ->getAllForUser($user, PaymentTransaction::STATUS_SUCCESS);

        $newCount = array_reduce($transactions, function($carry, $el) {
            return $carry + ($el->getIsSeen() ? 0 : 1);
        }, 0);

        return [
            'user' => $user,
            'transactions' => $transactions,
            'count_total' => count($transactions),
            'count_new' => $newCount
        ];
    }

    public function cartAction()
    {
        return [
            'user' => $this->getUser(),
            'cart' => $this->get('app.shopping_cart')->getShoppingCart()
        ];
    }

    public function updateProfileAction(Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');
        $user = $this->getUser();
        $wasSubscribedToNewsletter = $user->getSubscribedToNewsletter();
        $form = $this->createForm(ProfileType::class, $user, [
            'method' => 'POST',
            'language_choices' => array_column($this->getParameter('cms.languages'), 'code')
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $user->setUsername($user->getEmail());

            if ($wasSubscribedToNewsletter && !$user->getSubscribedToNewsletter()) {
                $this->handleNewsletterUnsubscribe($user);
            } else if (!$wasSubscribedToNewsletter && $user->getSubscribedToNewsletter()) {
                $this->handleNewsletterSubscribe($user);
            }

            if ($form->get('subscribeToTipsAndTricks')->getData()) {
                // TODO Subscribe to tips and tricks
            }

            $premiumSearchProfileEnabled = $form->get('searchProfilePremiumEnabled')->getData() ? true : false;
            $searchParamsPremium = $this->getDoctrine()
                    ->getRepository('AppBundle:HouseSearchParams')
                    ->findForUser($user, SiteSection::SECTION_PREMIUM);
            $searchParamsPremium->setEnabled($premiumSearchProfileEnabled);

            $this->getDoctrine()->getManager()->persist($searchParamsPremium);
            $this->getDoctrine()->getManager()->flush($searchParamsPremium);

            $marketplaceSearchProfileEnabled = $form->get('searchProfileMarketplaceEnabled')->getData() ? true : false;
            $searchParamsMarket = $this->getDoctrine()
                    ->getRepository('AppBundle:HouseSearchParams')
                    ->findForUser($user, SiteSection::SECTION_MARKETPLACE);
            $searchParamsMarket->setEnabled($marketplaceSearchProfileEnabled);

            $this->getDoctrine()->getManager()->persist($searchParamsMarket);
            $this->getDoctrine()->getManager()->flush($searchParamsMarket);

            $userManager->updateUser($user);
            $this->addFlash('success', 'flash.profile_updated');
            return new JsonResponse();
        }

        return new JsonResponse(['errors' => ValidationUtil::getFormValidationMessages($form)], 400);
    }

    protected function createHouseSearchForm(HouseSearchParams $params)
    {
        $options = [
            'method' => 'POST'
        ];

        return $this->createForm(HouseFilterType::class, HouseSearchParamsArrayMapper::toArray($params), $options);
    }

    protected function handleNewsletterSubscribe(User $user)
    {
        $handler = $this->get('app.newsletter');

        try {
            $newsletterUser = $handler->getUser($user);
        } catch (TransferException $ex) {
            $this->addFlash('danger', 'user.newsletter_subscribe_error');
            $user->setSubscribedToNewsletter(false);
            return;
        }

        if ($newsletterUser) {
            if (!$handler->enableUser($user)) {
                $this->addFlash('danger', 'user.newsletter_subscribe_error');
                $user->setSubscribedToNewsletter(false);
            }
        } else {
            if (!$handler->addUser($user)) {
                $this->addFlash('danger', 'user.newsletter_subscribe_error');
                $user->setSubscribedToNewsletter(false);
            }
        }
    }

    protected function handleNewsletterUnsubscribe(User $user)
    {
        if (!$this->get('app.newsletter')->disableUser($user)) {
            $this->addFlash('danger', 'user.newsletter_unsubscribe_error');
            $user->setSubscribedToNewsletter(true);
        }
    }

}
