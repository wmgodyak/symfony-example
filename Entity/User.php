<?php

namespace AppBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use FOS\MessageBundle\Model\ParticipantInterface;
use FOS\UserBundle\Model\User as BaseUser;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass="UserRepository")
 * @Vich\Uploadable
 * @UniqueEntity("email")
 */
class User extends BaseUser implements ParticipantInterface
{

    use TimestampableEntity;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var DateTime
     */
    private $registeredAt;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $registeredOnSection;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $fullName;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $streetName;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $streetNumber;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $floor;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $side;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $postCode;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $city;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $phone;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $phoneMobile;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $image;

    /**
     * @Vich\UploadableField(mapping="default", fileNameProperty="image")
     * @var File
     */
    private $imageFile;

    /**
     * @ORM\Column(type="boolean")
     * @var boolean
     */
    private $isVip;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $facebookId;

    /**
     * @ORM\Column(type="boolean")
     * @var boolean
     */
    private $subscribedToNewsletter;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $preferredLocale;

    /**
     * @ORM\OneToMany(targetEntity="House", mappedBy="landlord")
     * @var ArrayCollection
     */
    private $ownedHouses;

    /**
     * @ORM\ManyToMany(targetEntity="House", inversedBy="usersThatFavorited")
     * @var ArrayCollection
     */
    private $favoriteHouses;

    /**
     * @ORM\OneToMany(targetEntity="PaymentTransaction", mappedBy="user")
     * @var ArrayCollection
     */
    private $paymentTransactions;

    /**
     * @ORM\ManyToMany(targetEntity="TeamMember", mappedBy="landlords")
     * @ORM\OrderBy({"position" = "DESC"})
     * @var Collection
     */
    private $teamMembers;

    public function __construct()
    {
        parent::__construct();
        $this->isVip = false;
        $this->subscribedToNewsletter = false;
        $this->ownedHouses = new ArrayCollection();
        $this->favoriteHouses = new ArrayCollection();
        $this->paymentTransactions = new ArrayCollection();
        $this->teamMembers = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->email;
    }

    public function getFirstName()
    {
        return explode(' ', $this->fullName)[0];
    }

    public function getId()
    {
        return $this->id;
    }

    public function getRegisteredAt()
    {
        return $this->registeredAt;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function getImageFile()
    {
        return $this->imageFile;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setRegisteredAt(DateTime $registeredAt = null)
    {
        $this->registeredAt = $registeredAt;
    }

    public function setImage($image)
    {
        $this->image = $image;
    }

    public function setImageFile(File $imageFile = null)
    {
        $this->imageFile = $imageFile;

        if ($imageFile) {
            $this->updatedAt = new DateTime();
        }
    }

    public function getOwnedHouses()
    {
        return $this->ownedHouses;
    }

    public function setOwnedHouses(ArrayCollection $ownedHouses)
    {
        $this->ownedHouses = $ownedHouses;
    }

    public function hasInactiveHouses()
    {
        /* @var $house House */
        foreach ($this->ownedHouses as $house) {
            if (!$house->getMarketplacePublicationDate() &&
                    !$house->getPremiumPublicationDate()) {

                return true;
            }
        }
        return false;
    }

    public function getFavoriteHouses()
    {
        return $this->favoriteHouses;
    }

    public function setFavoriteHouses(ArrayCollection $favoriteHouses)
    {
        $this->favoriteHouses = $favoriteHouses;
    }

    public function getIsVip()
    {
        return $this->isVip;
    }

    public function setIsVip($isVip)
    {
        $this->isVip = $isVip;
    }

    public function getFullName()
    {
        return $this->fullName;
    }

    public function setFullName($fullName)
    {
        $this->fullName = $fullName;
    }

    public function getFacebookId()
    {
        return $this->facebookId;
    }

    public function setFacebookId($facebookId)
    {
        $this->facebookId = $facebookId;
    }

    public function getStreetName()
    {
        return $this->streetName;
    }

    public function getStreetNumber()
    {
        return $this->streetNumber;
    }

    public function getFloor()
    {
        return $this->floor;
    }

    public function getSide()
    {
        return $this->side;
    }

    public function getPostCode()
    {
        return $this->postCode;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function getPhoneMobile()
    {
        return $this->phoneMobile;
    }

    public function setStreetName($streetName)
    {
        $this->streetName = $streetName;
    }

    public function setStreetNumber($streetNumber)
    {
        $this->streetNumber = $streetNumber;
    }

    public function setFloor($floor)
    {
        $this->floor = $floor;
    }

    public function setSide($side)
    {
        $this->side = $side;
    }

    public function setPostCode($postCode)
    {
        $this->postCode = $postCode;
    }

    public function setCity($city)
    {
        $this->city = $city;
    }

    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    public function setPhoneMobile($phoneMobile)
    {
        $this->phoneMobile = $phoneMobile;
    }

    public function getPaymentTransactions()
    {
        return $this->paymentTransactions;
    }

    public function setPaymentTransactions(ArrayCollection $paymentTransactions)
    {
        $this->paymentTransactions = $paymentTransactions;
    }

    public function getSubscribedToNewsletter()
    {
        return $this->subscribedToNewsletter;
    }

    public function setSubscribedToNewsletter($subscribedToNewsletter)
    {
        $this->subscribedToNewsletter = $subscribedToNewsletter;
    }

    public function getPreferredLocale()
    {
        return $this->preferredLocale;
    }

    public function setPreferredLocale($preferredLocale)
    {
        $this->preferredLocale = $preferredLocale;
    }

    public function getRegisteredOnSection()
    {
        return $this->registeredOnSection;
    }

    public function setRegisteredOnSection($registeredOnSection)
    {
        $this->registeredOnSection = $registeredOnSection;
    }

    public function getTeamMembers()
    {
        return $this->teamMembers;
    }

    public function addTeamMember(TeamMember $teamMember)
    {
        if ($this->teamMembers->contains($teamMember)){
            return;
        }

        $this->teamMembers->add($teamMember);
        $teamMember->addLandlord($this);
    }

    public function removeTeamMember(TeamMember $teamMember)
    {
        if (!$this->teamMembers->contains($teamMember)) {
            return;
        }
        $this->teamMembers->removeElement($teamMember);
        $teamMember->removeLandlord($this);
    }
}
