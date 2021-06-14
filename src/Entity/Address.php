<?php

namespace App\Entity;

use App\Repository\AddressRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AddressRepository::class)
 */
class Address
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $company;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $street;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $street_format;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $zip;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $city;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $country;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $gender;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $first_name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $last_name;

    /**
     * @ORM\Column(type="boolean", options={"default":true})
     */
    private $status = true;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $comment;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $position;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $file_url;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $var1;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $var2;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $var3;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $var4;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $var5;

    /**
     * @ORM\Column(type="boolean", options={"default":false})
     */
    private $blacklist;

    /**
     * @ORM\ManyToOne(targetEntity=Pool::class, inversedBy="addresses")
     */
    private $pool;

    /**
     * @ORM\ManyToOne(targetEntity=Reaction::class, inversedBy="addresses")
     */
    private $reaction;

    /**
     * @ORM\OneToMany(targetEntity=Campaign::class, mappedBy="address")
     */
    private $campaigns;

    public function __construct()
    {
        $this->campaigns = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getStreetFormat(): ?string
    {
        return $this->street_format;
    }

    public function setStreetFormat(?string $street_format): self
    {
        $this->street_format = $street_format;

        return $this;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(?string $zip): self
    {
        $this->zip = $zip;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(?string $first_name): self
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(?string $last_name): self
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getFileUrl(): ?string
    {
        return $this->file_url;
    }

    public function setFileUrl(?string $file_url): self
    {
        $this->file_url = $file_url;

        return $this;
    }

    public function getVar1(): ?string
    {
        return $this->var1;
    }

    public function setVar1(?string $var1): self
    {
        $this->var1 = $var1;

        return $this;
    }

    public function getVar2(): ?string
    {
        return $this->var2;
    }

    public function setVar2(?string $var2): self
    {
        $this->var2 = $var2;

        return $this;
    }

    public function getVar3(): ?string
    {
        return $this->var3;
    }

    public function setVar3(?string $var3): self
    {
        $this->var3 = $var3;

        return $this;
    }

    public function getVar4(): ?string
    {
        return $this->var4;
    }

    public function setVar4(?string $var4): self
    {
        $this->var4 = $var4;

        return $this;
    }

    public function getVar5(): ?string
    {
        return $this->var5;
    }

    public function setVar5(?string $var5): self
    {
        $this->var5 = $var5;

        return $this;
    }

    public function getBlacklist(): ?bool
    {
        return $this->blacklist;
    }

    public function setBlacklist(bool $blacklist): self
    {
        $this->blacklist = $blacklist;

        return $this;
    }

    public function getPool(): ?Pool
    {
        return $this->pool;
    }

    public function setPool(?Pool $pool): self
    {
        $this->pool = $pool;

        return $this;
    }

    public function getReaction(): ?Reaction
    {
        return $this->reaction;
    }

    public function setReaction(?Reaction $reaction): self
    {
        $this->reaction = $reaction;

        return $this;
    }

    /**
     * @return Collection|Campaign[]
     */
    public function _getCampaigns(): Collection
    {
        return $this->campaigns;
    }

    public function addCampaign(Campaign $campaign): self
    {
        if (!$this->campaigns->contains($campaign)) {
            $this->campaigns[] = $campaign;
            $campaign->setAddress($this);
        }

        return $this;
    }

    public function removeCampaign(Campaign $campaign): self
    {
        if ($this->campaigns->removeElement($campaign)) {
            // set the owning side to null (unless already changed)
            if ($campaign->getAddress() === $this) {
                $campaign->setAddress(null);
            }
        }

        return $this;
    }
}
