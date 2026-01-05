<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as FormTypes;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UploadFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isAdmin = $options['is_admin_form'];
        $preselectedExpiration = $options['preselected_auto_expire_value'];

        $builder
            ->add('file', FormTypes\FileType::class, [
                'label' => 'Choose a file',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Assert\File(
                        maxSize: $isAdmin ? '256M' : '5M', // TODO: Dynamic based if admin (unlimited) or guest-link with limited/unlimited file-size
                    )
                ],
            ]);
        $builder->add('auto_expire', FormTypes\ChoiceType::class, [
            'label' => 'Expiration',
            'choices' => self::defaultExpirationChoices(),
            'data' => $preselectedExpiration, // null -> never
            'required' => true,
            'mapped' => false,
        ]);


        if ($isAdmin) {
            $builder->add('note', FormTypes\TextareaType::class, [
                'label' => 'note',
                'help' => 'Note is only visible to you',
                'required' => false,
            ]);
        }

        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmitEventHandler']);
    }

    public function postSubmitEventHandler(FormEvent $postSubmitEv): void
    {
        $form = $postSubmitEv->getForm();
        if (!$form->isValid()) {
            return;
        }

        // $form->addError(new FormError(__METHOD__ . ' momentski...')); // FIXME: Debug-purpose only
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'is_admin_form' => false,
            'preselected_auto_expire_value' => null,
        ]);
        $resolver->setAllowedTypes('is_admin_form', ['bool']);
        $resolver->setAllowedTypes('preselected_auto_expire_value', ['string', 'null']);
    }

    /**
     * @return list<string, string|null>
     */
    private static function defaultExpirationChoices(): array
    {
        // TODO: Extract into Separate class/enum to prevent redundancy
        return [
            '1 day' => '1-day',
            '7 days' => '7-day',
            '30 days' => '30-day',
            '1 year' => '1-year',
            'Never' => null,
            'Custom' => -1,
        ];
    }
}
