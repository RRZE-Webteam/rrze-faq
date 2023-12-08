/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
import { registerBlockType } from "@wordpress/blocks";

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * All files containing `style` keyword are bundled together. The code used
 * gets applied both to the front of your site and to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */

/**
 * Internal dependencies
 */
import Edit from "./edit";
import save from "./save";
import metadata from "./block.json";

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
registerBlockType(metadata.name, {
  icon: {
		src: "book-alt",
		background: "#00458c"
	},
  transforms: {
    from: [
      {
        type: "shortcode",
        tag: "faq",
        attributes: {
          glossary: {
            type: "string",
            shortcode: (attrs) => attrs.named.glossary,
          },
          glossarystyle: {
            type: "string",
            shortcode: (attrs) => attrs.named.glossarystyle,
          },
          category: {
            type: "string",
            shortcode: (attrs) => attrs.named.category,
          },
          tag: {
            type: "string",
            shortcode: (attrs) => attrs.named.tag,
          },
          id: {
            type: "string",
            shortcode: (attrs) => attrs.named.id,
          },
          hide_accordion: {
            type: "string",
            shortcode: (attrs) => attrs.named.hide_accordion,
          },
          hide_title: {
            type: "string",
            shortcode: (attrs) => attrs.named.hide_title,
          },
          expand_all_link: {
            type: "string",
            shortcode: (attrs) => attrs.named.expand_all_link,
          },
          load_open: {
            type: "string",
            shortcode: (attrs) => attrs.named.load_open,
          },
          color: {
            type: "string",
            shortcode: (attrs) => attrs.named.color,
          },
          additional_class: {
            type: "string",
            shortcode: (attrs) => attrs.named.additional_class,
          },
          lang: {
            type: "string",
            shortcode: (attrs) => attrs.named.lang,
          },
          sort: {
            type: "string",
            shortcode: (attrs) => attrs.named.sort,
          },
          order: {
            type: "string",
            shortcode: (attrs) => attrs.named.order,
          },
          hstart: {
            type: "string",
            shortcode: (attrs) => attrs.named.hstart,
          },
        },
      },
    ],
  },
  /**
   * @see ./edit.js
   */
  edit: Edit,

  /**
   * @see ./save.js
   */
  save,
});


