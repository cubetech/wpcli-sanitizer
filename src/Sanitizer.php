<?php

namespace Cubetech\WPCli;

use WP_CLI;
use WP_CLI_Command;

/**
 * Sanitize Wordpress attachments if their filename differs from the filename resulted from sanitize_title
 *
 * @see sanitize_title()
 * @version 1.0.0
 */
class Sanitizer extends WP_CLI_Command
{
	/**
	 * Sanitizes WP attachments in Database and renames files on disk if the filename differs from sanitize_title().
	 *
	 *
	 * ## EXAMPLES
	 *
	 *     wp media ct-sanitize
	 *
	 * @when after_wp_load
	 */
	public function __invoke($args, $assoc_args)
	{
		$posts = $this->getAttachmentPosts();
		$sanitizedCount = 0;
		foreach ($posts as $post) {
			$attachment = get_attached_file($post->ID);
			$wasSanitized = $this->sanitizeAttachmentIfNeeded($post->ID, $attachment);
			$sanitizedCount = $wasSanitized ? $sanitizedCount+1 : $sanitizedCount;
		}
		WP_CLI::success("Sanitized $sanitizedCount attachments.");
	}

	/**
	 * Returns all attachments from the WP Database.
	 *
	 * @return array All WP Attachments
	 */
	private function getAttachmentPosts()
	{
		$query = new \WP_Query([
			'post_status' => 'inherit',
			'post_type'=> 'attachment',
			'posts_per_page' => -1,
		]);

		return $query->posts;
	}

	/**
	 * Sanitizes a given attachment if the sanitized name differs from the original name.
	 *
	 * @param  int $attachmentID    ID of the attachment to be sanitized.
	 * @param  string $attachment   Path of the attachment to be sanitized.
	 */
	private function sanitizeAttachmentIfNeeded($attachmentID, $attachment)
	{
		$attachmentUrl = wp_get_attachment_url($attachmentID);
		$attachmentPathInfo = pathinfo($attachment);
		$sanitizedFilename = sanitize_title($attachmentPathInfo['filename']);

		//Only update attachment if the filename differs
		if ($sanitizedFilename != $attachmentPathInfo['filename']) {
			$sanitizedAttachment = "{$attachmentPathInfo['dirname']}/{$sanitizedFilename}.{$attachmentPathInfo['extension']}";

			if (! update_attached_file($attachmentID, $sanitizedAttachment)) {
				WP_CLI::error("Failed updating attachment with ID $attachmentID", false);
			}
			$attachmentMetaData = wp_generate_attachment_metadata($attachmentID, $sanitizedAttachment);
			wp_update_attachment_metadata($attachmentID, $attachmentMetaData);
			$this->renameFile($attachment, $sanitizedAttachment);
			$sanitizedAttachmentUrl = wp_get_attachment_url($attachmentID);
			$this->replacePostContents($attachmentUrl, $sanitizedAttachmentUrl);
			WP_CLI::line('Sanitized: ' . $attachmentPathInfo['filename']);
			return true;
		}

		return false;
	}

	/**
	 * Renames files which were previously sanitized in the WP Database.
	 */
	private function renameFile($oldName, $newName)
	{
		if (! rename($oldName, $newName)) {
			WP_CLI::error("Failed renaming $oldName to $newName", false);
		}
	}

	private function replacePostContents($old, $new)
	{
		WP_CLI::runcommand("search-replace $old $new wp_posts --verbose");
	}
}
