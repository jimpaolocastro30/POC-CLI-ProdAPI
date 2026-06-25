output "gke_cluster_name" {
  value = google_container_cluster.inventory.name
}

output "cloud_sql_connection" {
  value = google_sql_database_instance.inventory.connection_name
}

output "redis_host" {
  value = google_redis_instance.inventory.host
}

output "storage_buckets" {
  value = [
    google_storage_bucket.documents.name,
    google_storage_bucket.backups.name,
    google_storage_bucket.images.name,
  ]
}

output "artifact_registry" {
  value = google_artifact_registry_repository.inventory.id
}
