# Generated by Django 3.2.7 on 2021-09-18 11:08

from django.db import migrations


class Migration(migrations.Migration):

    dependencies = [
        ('main', '0003_auto_20210918_1358'),
    ]

    operations = [
        migrations.AlterUniqueTogether(
            name='adjacent',
            unique_together=set(),
        ),
        migrations.AlterUniqueTogether(
            name='contained',
            unique_together=set(),
        ),
        migrations.AlterUniqueTogether(
            name='prerequisite',
            unique_together=set(),
        ),
    ]