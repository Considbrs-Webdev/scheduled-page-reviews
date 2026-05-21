/**
 * Bridge @wordpress/i18n imports to the runtime WordPress global (wp-i18n script).
 */
import { wp } from "../editor/wp";

export const __ = wp.i18n.__;
export const sprintf = wp.i18n.sprintf;
export const _n = wp.i18n._n;
