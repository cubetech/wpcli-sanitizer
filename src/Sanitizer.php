<?php

namespace Cubetech\WPCli;

use WP_CLI;
use WP_CLI_Command;

/**
 * Sanitize Wordpress attachments if their filename differs from the filename resulted from sanitize_title
 * @see sanitize_title()
 */
class Sanitizer extends WP_CLI_Command
{
	protected $sanitizedAttachments = [];

	/**
	 * Sanitizes filenames in Database (guid) and renames the files in storage (wp-content/uploads).
	 *
	 *
	 * ## EXAMPLES
	 *
	 *     wp sanitize
	 *
	 * @when after_wp_load
	 */
	public function __invoke($args, $assoc_args)
	{
		$this->db();
		$this->files();
	}

	
	/**
	 * Sanitizes WP attachments in Database (_wp_attached_file).
	 */
	public function db()
	{
		$posts = $this->getAttachmentPosts();
		foreach ($posts as $post) {
			$attachment = get_attached_file($post->ID);
			$this->sanitizeAttachmentIfNeeded($post->ID, $attachment);
		}
		WP_CLI::success("Sanitized " . count($this->sanitizedAttachments) . " attachments.");
	}

	/**
	 * Renames files which were previously sanitized in the WP Database.
	 */
	public function files()
	{
		$successfulRenames = 0;

		foreach ($this->sanitizedAttachments as $oldName => $newName) {
			$result = rename($oldName, $newName);
			if ($result) {
				$successfulRenames++;
			} else {
				WP_CLI::error("Failed renaming $oldName to $newName");
			}
		}
		WP_CLI::success("Renamed $successfulRenames files.");
	}

	/**
	 * Returns all attachments from the WP Database.
	 * @return [array] All WP Attachments
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
	 * @param  [int] $attachmentID    ID of the attachment to be sanitized.
	 * @param  [string] $attachment   Path of the attachment to be sanitized.
	 */
	private function sanitizeAttachmentIfNeeded($attachmentID, $attachment)
	{
		$attachmentPathInfo = pathinfo($attachment);
		$sanitizedFilename = sanitize_title($attachmentPathInfo['filename']);

        //Only update attachment if the filename differs
		if ($sanitizedFilename != $attachmentPathInfo['filename']) {
			$sanitizedAttachment = "{$attachmentPathInfo['dirname']}/{$sanitizedFilename}.{$attachmentPathInfo['extension']}";
			$this->sanitizedAttachments[$attachment] = $sanitizedAttachment;
			
            $result = update_attached_file($attachmentID, $sanitizedAttachment);

			if (! $result) {
				WP_CLI::error("Failed updating attachment with ID $attachmentID");
			}
		}
	}
}