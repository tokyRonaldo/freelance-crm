<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "L'échéance est obligatoire.")]
    private ?\DateTimeImmutable $dueAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    private ?Contact $contact = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Opportunity $opportunity = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    private ?User $owner = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDueAt(): ?\DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function setDueAt(\DateTimeImmutable $dueAt): static
    {
        $this->dueAt = $dueAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getOpportunity(): ?Opportunity
    {
        return $this->opportunity;
    }

    public function setOpportunity(?Opportunity $opportunity): static
    {
        $this->opportunity = $opportunity;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    #[ORM\Column(enumType: TaskPriority::class)]
    private TaskPriority $priority = TaskPriority::Normal;

    #[ORM\Column(enumType: TaskStatus::class)]
    private TaskStatus $status = TaskStatus::Todo;

    public function getPriority(): TaskPriority
    {
        return $this->priority;
    }

    public function setPriority(TaskPriority $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function setStatus(TaskStatus $status): static
    {
        $this->status = $status;
        // Règle : la date d'achèvement suit le statut (effacée si on rouvre la tâche).
        $this->completedAt = $status === TaskStatus::Done
            ? ($this->completedAt ?? new \DateTimeImmutable())
            : null;

        return $this;
    }

    public function isOverdue(): bool
    {
        // Règle 5 : échéance passée et tâche non terminée. Calculé, pas stocké.
        return $this->status !== TaskStatus::Done
            && $this->dueAt < new \DateTimeImmutable('today');
    }

    #[Assert\Callback]
    public function validateLinks(ExecutionContextInterface $context): void
    {
        if ($this->contact && $this->opportunity && $this->opportunity->getContact() !== $this->contact) {
            $context->buildViolation("Cette opportunité n'appartient pas au contact choisi.")
                ->atPath('opportunity')
                ->addViolation();
        }
    }
}
