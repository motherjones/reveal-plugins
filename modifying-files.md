
## Changes to Podcast Episode Handling

The demonstrated changes involve altering how podcast episodes function to retrieve audio assets from PRX instead of Amazon.

### Steps to Update the PRX Embed URL:
1. Go to the Episode post.
2. Scroll to the bottom.
3. Locate the field labeled "PRX embed URL".
4. Insert the PRX URL from the iframe.

## Updating the Reveal Implementation of Newspack Podcasts

To make changes to the Reveal implementation of Newspack Podcasts, follow these steps:

### Access the Repository
- **Branch**: [podcast-prx branch on GitHub](https://github.com/motherjones/reveal-plugins/tree/podcast-prx)

### Making Changes:
1. Make your changes in the repository.
2. Go to the files listed in your branch's diff.
3. Copy the contents of each file as changed into corresponding file in ** Plugin Editor **.
4. Navigate to **Plugins** -> **Plugin File Editor**.
5. Navigate to file corresponding with files changed in repos.
6. Pasted contents copied in step 3.

#### Update Files:
1. **Class File for Custom Post Types (CPT)**:
   - **Local Editor Link**: [newspack-podcasts/includes/class-newspack-podcasts-cpt.php in WP Plugin Editor](https://revealnews.newspackstaging.com/wp-admin/plugin-editor.php?file=newspack-podcasts%2Fincludes%2Fclass-newspack-podcasts-cpt.php&plugin=newspack-podcasts%2Fnewspack-podcasts.php)
   - **Repository File Link**: [class-newspack-podcasts-cpt.php on GitHub](https://github.com/motherjones/reveal-plugins/blob/podcast-prx/Newspack-Podcasts-1.2.1/includes/class-newspack-podcasts-cpt.php)

2. **Class File for Frontend**:
   - **Local Editor Link**: [newspack-podcasts/includes/class-newspack-podcasts-frontend.php in WP Plugin Editor](https://revealnews.newspackstaging.com/wp-admin/plugin-editor.php?file=newspack-podcasts%2Fincludes%2Fclass-newspack-podcasts-frontend.php&plugin=newspack-podcasts%2Fnewspack-podcasts.php)
   - **Repository File Link**: [class-newspack-podcasts-frontend.php on GitHub](https://github.com/motherjones/reveal-plugins/blob/podcast-prx/Newspack-Podcasts-1.2.1/includes/class-newspack-podcasts-frontend.php)

