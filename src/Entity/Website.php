<?php

namespace App\Entity;

use App\Repository\WebsiteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WebsiteRepository::class)]
class Website
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $url;

    #[ORM\Column(type: 'string', length: 255)]
    private $shorturl;

    #[ORM\Column(type: 'integer')]
    private $visited;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'website')]
    private $user;

    #[ORM\Column(type: 'string', length: 255)]
    private $hash;

    #[ORM\Column(type: 'integer')]
    private $status;

    #[ORM\Column(type: 'string', length: 255)]
    private $created_date;

    #[ORM\Column(type: 'boolean')]
    private $include;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getShorturl(): ?string
    {
        return $this->shorturl;
    }

    public function setShorturl(string $shorturl): self
    {
        $this->shorturl = $shorturl;

        return $this;
    }

    public function getVisited(): ?int
    {
        return $this->visited;
    }

    public function setVisited(int $visited): self
    {
        $this->visited = $visited;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedDate(): ?string
    {
        return $this->created_date;
    }

    public function setCreatedDate(string $created_date): self
    {
        $this->created_date = $created_date;

        return $this;
    }

    public function isInclude(): ?bool
    {
        return $this->include;
    }

    public function setInclude(bool $include): self
    {
        $this->include = $include;

        return $this;
    }
}
