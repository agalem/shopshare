<?php
/**
 * Created by PhpStorm.
 * User: agalempaszek
 * Date: 11.06.2018
 * Time: 11:58
 */


namespace Form;

use PHP_CodeSniffer\Generators\Text;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ListConnectionType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->add(
			'user_login',
			TextType::class,
			[
				'label' => 'label.user_login',
				'required' => true,
				'attr' => [
					'max_length' => 45,
				],
			]
		);

	}



	public function getBlockPrefix() {
		return 'list-connection_type';
	}

}