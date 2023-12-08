//Imports for necessary WordPress libraries
import { __ } from "@wordpress/i18n";
import { ToolbarGroup, ToolbarItem, ToolbarButton } from "@wordpress/components";
import { trash } from "@wordpress/icons";
import { useBlockProps, BlockControls } from "@wordpress/block-editor"; // eslint-disable-line import/no-unresolved
import { ServerSideRender } from "@wordpress/editor"; // eslint-disable-line import/no-unresolved
import { useState, useRef } from "@wordpress/element";

//Imports for custom components
import {HeadingSelector} from "./CustomComponents/HeadingSelector";

//Imports for helper functions
import { isTextInString } from "./HelperFunctions/utils";

//Import the Editor Styles for the blockeditor
import "./editor.scss"; //Only active in the editor

/**
 * 
 * @param {*} props 
 * @returns 
 */
export default function Edit(props) {
  const uniqueId = Math.random().toString(36).substring(2, 15);

  // Create a ref to the container div
  const containerRef = useRef();
  const blockProps = useBlockProps();
  const { attributes, setAttributes } = props;
  const { category, tag, id } = attributes;

  
  /**
   * Renders the FAQ block
   */
  return (
    <div {...blockProps}>
      <CustomInspectorControls attributes={attributes} setAttributes={setAttributes} />
      {category || tag || id ? (
        <>
        <div
          className={`rrze-faq-container-${uniqueId}`}
          ref={containerRef}
        >
        {/* Renders dynamic Shortcode from includes/Gutenberg.php */}
            <ServerSideRender
              block="rrze/rrze-faq"
              attributes={{
                // glossary: attributes.glossary,
                // glossarystyle: attributes.glossarystyle,
                category: attributes.category,
                tag: attributes.tag,
                id: attributes.id,
                // hide_accordion: attributes.hide_accordion,
                // hide_title: attributes.hide_title,
                // expand_all_link: attributes.expand_all_link,
                // load_open: attributes.load_open,
                // color: attributes.color,
                // additional_class: attributes.additional_class,
                // lang: attributes.lang,
                // sort: attributes.sort,
                // order: attributes.order,
                // hstart: attributes.hstart,                
              }}
              />
            </div>
          </>
        ) : (
          <CustomPlaceholder attributes={attributes} setAttributes={setAttributes} />
        )}
      </div>
    );
  }
  