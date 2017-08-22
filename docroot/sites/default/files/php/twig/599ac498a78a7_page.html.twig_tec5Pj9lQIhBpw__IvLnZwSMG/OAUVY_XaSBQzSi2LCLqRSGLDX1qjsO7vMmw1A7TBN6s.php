<?php

/* themes/custom/site/templates/page/page.html.twig */
class __TwigTemplate_545a7a4d7789627b0f77dfe1c24ea634221ec939970cb0c6fde6a94b452063f0 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
            'sidebar_first' => array($this, 'block_sidebar_first'),
            'main' => array($this, 'block_main'),
            'navbar' => array($this, 'block_navbar'),
            'header' => array($this, 'block_header'),
            'highlighted' => array($this, 'block_highlighted'),
            'breadcrumb' => array($this, 'block_breadcrumb'),
            'action_links' => array($this, 'block_action_links'),
            'help' => array($this, 'block_help'),
            'content' => array($this, 'block_content'),
            'sidebar_second' => array($this, 'block_sidebar_second'),
            'footer' => array($this, 'block_footer'),
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        $tags = array("set" => 59, "if" => 66, "block" => 67);
        $filters = array("clean_class" => 84, "t" => 93);
        $functions = array();

        try {
            $this->env->getExtension('Twig_Extension_Sandbox')->checkSecurity(
                array('set', 'if', 'block'),
                array('clean_class', 't'),
                array()
            );
        } catch (Twig_Sandbox_SecurityError $e) {
            $e->setSourceContext($this->getSourceContext());

            if ($e instanceof Twig_Sandbox_SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof Twig_Sandbox_SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof Twig_Sandbox_SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

        // line 59
        $context["container"] = (($this->getAttribute($this->getAttribute((isset($context["theme"]) ? $context["theme"] : null), "settings", array()), "fluid_container", array())) ? ("container-fluid") : ("container"));
        // line 60
        echo "
<aside class=\"left-container \" role=\"complementary\">
  dsgjfjskdjg
  dlgkdgs
  sdgadg
";
        // line 66
        if ($this->getAttribute((isset($context["page"]) ? $context["page"] : null), "sidebar_first", array())) {
            // line 67
            echo "  ";
            $this->displayBlock('sidebar_first', $context, $blocks);
        }
        // line 71
        echo "</aside>

";
        // line 74
        $this->displayBlock('main', $context, $blocks);
    }

    // line 67
    public function block_sidebar_first($context, array $blocks = array())
    {
        // line 68
        echo "    ";
        echo $this->env->getExtension('Twig_Extension_Sandbox')->ensureToStringAllowed($this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->getAttribute((isset($context["page"]) ? $context["page"] : null), "sidebar_first", array()), "html", null, true));
        echo "
  ";
    }

    // line 74
    public function block_main($context, array $blocks = array())
    {
        // line 75
        echo "  <div role=\"main\" class=\"main-container ";
        echo $this->env->getExtension('Twig_Extension_Sandbox')->ensureToStringAllowed($this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, (isset($context["container"]) ? $context["container"] : null), "html", null, true));
        echo " js-quickedit-main-content\">
    
    ";
        // line 78
        echo "    ";
        if (($this->getAttribute((isset($context["page"]) ? $context["page"] : null), "navigation", array()) || $this->getAttribute((isset($context["page"]) ? $context["page"] : null), "navigation_collapsible", array()))) {
            // line 79
            echo "      ";
            $this->displayBlock('navbar', $context, $blocks);
            // line 109
            echo "    ";
        }
        // line 110
        echo "    
";
        // line 112
        echo "
      ";
        // line 121
        echo "      ";
        if ($this->getAttribute((isset($context["page"]) ? $context["page"] : null), "header", array())) {
            // line 122
            echo "        ";
            $this->displayBlock('header', $context, $blocks);
            // line 127
            echo "      ";
        }
        // line 128
        echo "
      <div class=\"row main-container__content\">
        ";
        // line 131
        echo "        ";
        // line 132
        $context["content_classes"] = array(0 => (($this->getAttribute(        // line 133
(isset($context["page"]) ? $context["page"] : null), "sidebar_second", array())) ? ("col-md-9") : ("")), 1 => ((twig_test_empty($this->getAttribute(        // line 134
(isset($context["page"]) ? $context["page"] : null), "sidebar_second", array()))) ? ("col-md-12") : ("")));
        // line 137
        echo "  ";
        // line 145
        echo "        <section";
        echo $this->env->getExtension('Twig_Extension_Sandbox')->ensureToStringAllowed($this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->getAttribute((isset($context["content_attributes"]) ? $context["content_attributes"] : null), "addClass", array(0 => (isset($context["content_classes"]) ? $context["content_classes"] : null)), "method"), "html", null, true));
        echo ">

          ";
        // line 148
        echo "          ";
        if ($this->getAttribute((isset($context["page"]) ? $context["page"] : null), "highlighted", array())) {
            // line 149
            echo "            ";
            $this->displayBlock('highlighted', $context, $blocks);
            // line 152
            echo "          ";
        }
        // line 153
        echo "
          ";
        // line 155
        echo "          ";
        if ((isset($context["breadcrumb"]) ? $context["breadcrumb"] : null)) {
            // line 156
            echo "            ";
            $this->displayBlock('breadcrumb', $context, $blocks);
            // line 159
            echo "          ";
        }
        // line 160
        echo "
          ";
        // line 162
        echo "          ";
        if ((isset($context["action_links"]) ? $context["action_links"] : null)) {
            // line 163
            echo "            ";
            $this->displayBlock('action_links', $context, $blocks);
            // line 166
            echo "          ";
        }
        // line 167
        echo "
          ";
        // line 169
        echo "          ";
        if ($this->getAttribute((isset($context["page"]) ? $context["page"] : null), "help", array())) {
            // line 170
            echo "            ";
            $this->displayBlock('help', $context, $blocks);
            // line 173
            echo "          ";
        }
        // line 174
        echo "
          ";
        // line 176
        echo "          ";
        $this->displayBlock('content', $context, $blocks);
        // line 180
        echo "        </section>

        ";
        // line 183
        echo "        ";
        if ($this->getAttribute((isset($context["page"]) ? $context["page"] : null), "sidebar_second", array())) {
            // line 184
            echo "          ";
            $this->displayBlock('sidebar_second', $context, $blocks);
            // line 189
            echo "        ";
        }
        // line 190
        echo "      </div>
";
        // line 192
        echo "
  ";
        // line 193
        if ($this->getAttribute((isset($context["page"]) ? $context["page"] : null), "footer", array())) {
            // line 194
            echo "    ";
            $this->displayBlock('footer', $context, $blocks);
            // line 199
            echo "  ";
        }
        // line 200
        echo "
  </div>
";
    }

    // line 79
    public function block_navbar($context, array $blocks = array())
    {
        // line 80
        echo "        ";
        // line 81
        $context["navbar_classes"] = array(0 => "navbar", 1 => (($this->getAttribute($this->getAttribute(        // line 83
(isset($context["theme"]) ? $context["theme"] : null), "settings", array()), "navbar_inverse", array())) ? ("navbar-inverse") : ("navbar-default")), 2 => (($this->getAttribute($this->getAttribute(        // line 84
(isset($context["theme"]) ? $context["theme"] : null), "settings", array()), "navbar_position", array())) ? (("navbar-" . \Drupal\Component\Utility\Html::getClass($this->getAttribute($this->getAttribute((isset($context["theme"]) ? $context["theme"] : null), "settings", array()), "navbar_position", array())))) : ((isset($context["container"]) ? $context["container"] : null))));
        // line 87
        echo "        <header";
        echo $this->env->getExtension('Twig_Extension_Sandbox')->ensureToStringAllowed($this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->getAttribute((isset($context["navbar_attributes"]) ? $context["navbar_attributes"] : null), "addClass", array(0 => (isset($context["navbar_classes"]) ? $context["navbar_classes"] : null)), "method"), "html", null, true));
        echo " id=\"navbar\" role=\"banner\">
          <div class=\"navbar-header\">
            ";
        // line 89
        echo $this->env->getExtension('Twig_Extension_Sandbox')->ensureToStringAllowed($this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->getAttribute((isset($context["page"]) ? $context["page"] : null), "navigation", array()), "html", null, true));
        echo "
            ";
        // line 91
        echo "            ";
        if ($this->getAttribute((isset($context["page"]) ? $context["page"] : null), "navigation_collapsible", array())) {
            // line 92
            echo "              <button type=\"button\" class=\"navbar-toggle\" data-toggle=\"collapse\" data-target=\".navbar-collapse\">
                <span class=\"sr-only\">";
            // line 93
            echo $this->env->getExtension('Twig_Extension_Sandbox')->ensureToStringAllowed($this->env->getExtension('Drupal\Core\Template\TwigExtension')->renderVar(t("Toggle navigation")));
            echo "</span>
                <span class=\"icon-bar\"></span>
                <span class=\"icon-bar\"></span>
                <span class=\"icon-bar\"></span>
              </button>
            ";
        }
        // line 99
        echo "          </div>

          ";
        // line 102
        echo "          ";
        if ($this->getAttribute((isset($context["page"]) ? $context["page"] : null), "navigation_collapsible", array())) {
            // line 103
            echo "            <div class=\"navbar-collapse collapse\">
              ";
            // line 104
            echo $this->env->getExtension('Twig_Extension_Sandbox')->ensureToStringAllowed($this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->getAttribute((isset($context["page"]) ? $context["page"] : null), "navigation_collapsible", array()), "html", null, true));
            echo "
            </div>
          ";
        }
        // line 107
        echo "        </header>
      ";
    }

    // line 122
    public function block_header($context, array $blocks = array())
    {
        // line 123
        echo "          <div class=\"col-sm-12\" role=\"heading\">
            ";
        // line 124
        echo $this->env->getExtension('Twig_Extension_Sandbox')->ensureToStringAllowed($this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->getAttribute((isset($context["page"]) ? $context["page"] : null), "header", array()), "html", null, true));
        echo "
          </div>
        ";
    }

    // line 149
    public function block_highlighted($context, array $blocks = array())
    {
        // line 150
        echo "              <div class=\"highlighted\">";
        echo $this->env->getExtension('Twig_Extension_Sandbox')->ensureToStringAllowed($this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->getAttribute((isset($context["page"]) ? $context["page"] : null), "highlighted", array()), "html", null, true));
        echo "</div>
            ";
    }

    // line 156
    public function block_breadcrumb($context, array $blocks = array())
    {
        // line 157
        echo "              ";
        echo $this->env->getExtension('Twig_Extension_Sandbox')->ensureToStringAllowed($this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, (isset($context["breadcrumb"]) ? $context["breadcrumb"] : null), "html", null, true));
        echo "
            ";
    }

    // line 163
    public function block_action_links($context, array $blocks = array())
    {
        // line 164
        echo "              <ul class=\"action-links\">";
        echo $this->env->getExtension('Twig_Extension_Sandbox')->ensureToStringAllowed($this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, (isset($context["action_links"]) ? $context["action_links"] : null), "html", null, true));
        echo "</ul>
            ";
    }

    // line 170
    public function block_help($context, array $blocks = array())
    {
        // line 171
        echo "              ";
        echo $this->env->getExtension('Twig_Extension_Sandbox')->ensureToStringAllowed($this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->getAttribute((isset($context["page"]) ? $context["page"] : null), "help", array()), "html", null, true));
        echo "
            ";
    }

    // line 176
    public function block_content($context, array $blocks = array())
    {
        // line 177
        echo "            <a id=\"main-content\"></a>
            ";
        // line 178
        echo $this->env->getExtension('Twig_Extension_Sandbox')->ensureToStringAllowed($this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->getAttribute((isset($context["page"]) ? $context["page"] : null), "content", array()), "html", null, true));
        echo "
          ";
    }

    // line 184
    public function block_sidebar_second($context, array $blocks = array())
    {
        // line 185
        echo "            <aside class=\"col-sm-3\" role=\"complementary\">
              ";
        // line 186
        echo $this->env->getExtension('Twig_Extension_Sandbox')->ensureToStringAllowed($this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->getAttribute((isset($context["page"]) ? $context["page"] : null), "sidebar_second", array()), "html", null, true));
        echo "
            </aside>
          ";
    }

    // line 194
    public function block_footer($context, array $blocks = array())
    {
        // line 195
        echo "      <footer class=\"footer ";
        echo $this->env->getExtension('Twig_Extension_Sandbox')->ensureToStringAllowed($this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, (isset($context["container"]) ? $context["container"] : null), "html", null, true));
        echo "\" role=\"contentinfo\">
        ";
        // line 196
        echo $this->env->getExtension('Twig_Extension_Sandbox')->ensureToStringAllowed($this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->getAttribute((isset($context["page"]) ? $context["page"] : null), "footer", array()), "html", null, true));
        echo "
      </footer>
    ";
    }

    public function getTemplateName()
    {
        return "themes/custom/site/templates/page/page.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  363 => 196,  358 => 195,  355 => 194,  348 => 186,  345 => 185,  342 => 184,  336 => 178,  333 => 177,  330 => 176,  323 => 171,  320 => 170,  313 => 164,  310 => 163,  303 => 157,  300 => 156,  293 => 150,  290 => 149,  283 => 124,  280 => 123,  277 => 122,  272 => 107,  266 => 104,  263 => 103,  260 => 102,  256 => 99,  247 => 93,  244 => 92,  241 => 91,  237 => 89,  231 => 87,  229 => 84,  228 => 83,  227 => 81,  225 => 80,  222 => 79,  216 => 200,  213 => 199,  210 => 194,  208 => 193,  205 => 192,  202 => 190,  199 => 189,  196 => 184,  193 => 183,  189 => 180,  186 => 176,  183 => 174,  180 => 173,  177 => 170,  174 => 169,  171 => 167,  168 => 166,  165 => 163,  162 => 162,  159 => 160,  156 => 159,  153 => 156,  150 => 155,  147 => 153,  144 => 152,  141 => 149,  138 => 148,  132 => 145,  130 => 137,  128 => 134,  127 => 133,  126 => 132,  124 => 131,  120 => 128,  117 => 127,  114 => 122,  111 => 121,  108 => 112,  105 => 110,  102 => 109,  99 => 79,  96 => 78,  90 => 75,  87 => 74,  80 => 68,  77 => 67,  73 => 74,  69 => 71,  65 => 67,  63 => 66,  56 => 60,  54 => 59,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "themes/custom/site/templates/page/page.html.twig", "/Users/stan/Development/AcquiaSites/2move/2move_app/docroot/themes/custom/site/templates/page/page.html.twig");
    }
}
