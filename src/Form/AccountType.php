<?php
/**
 * Login form.
 */

namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class LoginType.
 */
class AccountType extends AbstractType
{
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add(
			'login',
			TextType::class,
			[
				'label' => 'label.login',
				'required' => true,
				'attr' => [
					'max_length' => 32,

				],
				'constraints' => [
					new Assert\NotBlank(),
					new Assert\Length(
						[
							'min' => 8,
							'max' => 32,
						]
					),
				],
			]
		);
		$builder->add(
			'password',
			PasswordType::class,
			[
				'label' => 'label.password',
				'required' => true,
				'attr' => [
					'max_length' => 32,

				],
				'constraints' => [
					new Assert\NotBlank(),
					new Assert\Length(
						[
							'min' => 8,
							'max' => 32,
						]
					),
				],
			]
		);

	}

	/**
	 * {@inheritdoc}
	 */
	public function getBlockPrefix()
	{
		return 'account_type';
	}
}