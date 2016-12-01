<?php
return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules(array(
        'array_syntax' => array('syntax' => 'short'),
        'binary_operator_spaces' => true,
        'blank_line_after_namespace' => true,
        'blank_line_before_return' => true,
        'braces' => true,
        'cast_spaces' => true,
        'class_definition' => true,
        'combine_consecutive_unsets' => true,
        'declare_strict_types' => true,
        'elseif' => true,
        'encoding' => true,
        'full_opening_tag' => true,
        'function_declaration' => true,
        'indentation_type' => true,
        'line_ending' => true,
        'lowercase_constants' => true,
        'lowercase_keywords' => true,
        'method_argument_space' => true,
        'no_trailing_whitespace_in_comment' => true,
        'no_alias_functions' => true,
        'no_closing_tag' => true,
        'no_empty_statement' => true,
        'no_extra_consecutive_blank_lines' => array('break', 'continue', 'extra', 'return', 'throw', 'use', 'parenthesis_brace_block', 'square_brace_block', 'curly_brace_block'),
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'no_spaces_inside_parenthesis' => true,
        'no_whitespace_in_blank_line' => true,
        'not_operator_with_successor_space' => true,
        'object_operator_without_whitespace' => true,
        'ordered_imports' => true,
        'psr4' => true,
        'single_line_after_imports' => true,
        'short_scalar_cast' => true,
        'simplified_null_return' => true,
        'single_blank_line_at_eof' => true,
        'single_class_element_per_statement' => true,
        'single_import_per_statement' => true,
        'single_line_after_imports' => true,
        'standardize_not_equals' => true,
        'strict_comparison' => true,
        'switch_case_semicolon_to_colon' => true,
        'switch_case_space' => true,
        'visibility_required' => true,
    ))
    ->setFinder(
         PhpCsFixer\Finder::create()
             ->exclude('tests/Fixtures')
             ->in(__DIR__)
    )
;
