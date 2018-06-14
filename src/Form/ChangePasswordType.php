<?php

namespace Form;

use PHP_CodeSniffer\Generators\Text;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ChangePasswordType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->add(
			'password',
			TextType::class,
			[
				'label' => 'label.new_password',
				'required' => true,
				'attr' => [
					'max_length' => 45,
					'min_length' => 8,
				],
			]
		);

	}

	public function getBlockPrefix() {
		return 'change_password_type';
	}

}